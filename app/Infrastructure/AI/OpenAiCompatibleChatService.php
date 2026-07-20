<?php

namespace App\Infrastructure\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiCompatibleChatService
{
    /** @return array{provider: string, api_key: string, base_url: string, model: string}|null */
    public function resolveProvider(): ?array
    {
        foreach (['openrouter', 'gemini', 'bluesminds', 'openai'] as $provider) {
            $apiKey = config("services.{$provider}.api_key");

            if (! $apiKey) {
                continue;
            }

            return [
                'provider' => $provider,
                'api_key' => $apiKey,
                'base_url' => (string) (config("services.{$provider}.base_url") ?: ''),
                'model' => (string) config("services.{$provider}.model"),
            ];
        }

        return null;
    }

    /** @return list<string> */
    public function modelsForProvider(string $provider): array
    {
        $primary = (string) config("services.{$provider}.model");
        $fallbacks = config("services.{$provider}.fallback_models", []);

        if (! is_array($fallbacks)) {
            $fallbacks = [];
        }

        return array_values(array_unique(array_filter([$primary, ...$fallbacks])));
    }

    /** @return array{content: string, provider: string, model: string}|null */
    public function complete(
        string $systemPrompt,
        string $userPrompt,
        float $temperature = 0.7,
        ?array $responseFormat = null,
    ): ?array {
        $config = $this->resolveProvider();

        if (! $config) {
            throw new RuntimeException('No AI provider configured. Add OPENROUTER_API_KEY, GEMINI_API_KEY, BLUESMINDS_API_KEY, or OPENAI_API_KEY to .env.');
        }

        $models = $this->modelsForProvider($config['provider']);
        $lastError = 'AI request failed. Check your API key and try again.';

        foreach ($models as $model) {
            try {
                $result = $config['provider'] === 'gemini'
                    ? $this->requestGemini($config, $model, $systemPrompt, $userPrompt, $temperature, $responseFormat)
                    : $this->request($config, $model, $systemPrompt, $userPrompt, $temperature, $responseFormat);

                if ($result !== null) {
                    return $result;
                }
            } catch (RuntimeException $e) {
                $lastError = $e->getMessage();

                if (! $this->shouldRetryWithNextModel($e->getMessage())) {
                    throw $e;
                }

                Log::warning("{$config['provider']} model unavailable, trying fallback", [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException($lastError);
    }

    /** @return array{content: string, provider: string, model: string}|null */
    private function request(
        array $config,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        float $temperature,
        ?array $responseFormat,
    ): ?array {
        $payload = [
            'model' => $model,
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        if ($responseFormat !== null) {
            $payload['response_format'] = $responseFormat;
        }

        try {
            $client = $this->httpClient()->withToken($config['api_key']);

            if ($config['provider'] === 'openrouter') {
                $client = $client->withHeaders([
                    'HTTP-Referer' => (string) config('services.openrouter.referer', config('app.url')),
                    'X-Title' => (string) config('services.openrouter.title', config('app.name')),
                ]);
            }

            $response = $client
                ->timeout(120)
                ->post(rtrim($config['base_url'], '/').'/chat/completions', $payload);

            if (! $response->successful()) {
                $message = (string) ($response->json('error.message') ?: 'AI request failed.');

                Log::warning("{$config['provider']} chat completion failed", [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuntimeException($message);
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return null;
            }

            return [
                'content' => trim($content),
                'provider' => $config['provider'],
                'model' => $model,
            ];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning("{$config['provider']} chat completion exception", [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('AI request failed: '.$e->getMessage());
        }
    }

    /** @return array{content: string, provider: string, model: string}|null */
    private function requestGemini(
        array $config,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        float $temperature,
        ?array $responseFormat,
    ): ?array {
        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
            ],
        ];

        if (($responseFormat['type'] ?? null) === 'json_object') {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        $baseUrl = rtrim($config['base_url'] ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $url = "{$baseUrl}/models/{$model}:generateContent";

        try {
            $response = $this->httpClient()
                ->withHeaders(['x-goog-api-key' => $config['api_key']])
                ->timeout(120)
                ->post($url, $payload);

            if (! $response->successful()) {
                $message = (string) ($response->json('error.message') ?: 'AI request failed.');

                Log::warning('gemini chat completion failed', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuntimeException($message);
            }

            $content = $response->json('candidates.0.content.parts.0.text');

            if (! is_string($content) || trim($content) === '') {
                return null;
            }

            return [
                'content' => trim($content),
                'provider' => 'gemini',
                'model' => $model,
            ];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('gemini chat completion exception', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('AI request failed: '.$e->getMessage());
        }
    }

    private function shouldRetryWithNextModel(string $message): bool
    {
        $needles = [
            'no available channel',
            'no endpoints found',
            'model not found',
            'does not exist',
            'invalid model',
            'model_not_found',
            'not found',
            'is not supported',
            'unavailable for free',
            'quota exceeded',
            'rate limit',
            'provider returned error',
        ];

        $message = strtolower($message);

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::acceptJson();

        if (config('app.env') === 'local' && ! filter_var(env('HTTP_VERIFY_SSL', true), FILTER_VALIDATE_BOOL)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}
