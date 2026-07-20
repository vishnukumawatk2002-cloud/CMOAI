<?php

namespace App\Infrastructure\AI\Concerns;

use App\Infrastructure\AI\OpenAiCompatibleChatService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait AnalyzesKnowledgeBaseViaChatApi
{
    /** @return array<string, mixed>|null */
    protected function analyzeViaChatService(
        OpenAiCompatibleChatService $chat,
        string $providerLabel,
        array $sourceData,
    ): ?array {
        try {
            $result = $chat->complete(
                $this->knowledgeBaseSystemPrompt(),
                json_encode($sourceData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                0.4,
                ['type' => 'json_object'],
            );

            if (! $result) {
                return null;
            }

            $analysis = json_decode($result['content'], true);

            if (! is_array($analysis)) {
                return null;
            }

            $analysis['provider'] = $providerLabel;
            $analysis['model'] = $result['model'];

            return $analysis;
        } catch (\Throwable $e) {
            Log::warning("{$providerLabel} knowledge base exception", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array<string, mixed>|null */
    protected function analyzeViaChatApi(
        string $provider,
        string $apiKey,
        string $baseUrl,
        string $model,
        array $sourceData,
    ): ?array {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post(rtrim($baseUrl, '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->knowledgeBaseSystemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode($sourceData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning("{$provider} knowledge base request failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content)) {
                return null;
            }

            $analysis = json_decode($content, true);

            if (! is_array($analysis)) {
                return null;
            }

            $analysis['provider'] = $provider;
            $analysis['model'] = $model;

            return $analysis;
        } catch (\Throwable $e) {
            Log::warning("{$provider} knowledge base exception", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function knowledgeBaseSystemPrompt(): string
    {
        return <<<'PROMPT'
You are CMO AI's brand intelligence engine. Analyze the brand onboarding data and return JSON only.

Return this exact JSON shape:
{
  "brand_summary": "2-3 sentence brand overview",
  "detected_tone": "brand voice tone",
  "detected_audience": "target audience summary",
  "detected_services": "products and services summary",
  "top_keywords": ["keyword1", "keyword2"],
  "content_strategy": {
    "themes": ["educational", "promotional", "testimonials", "culture"],
    "pillars": ["content pillar 1", "content pillar 2"]
  },
  "suggested_prompts": [
    {
      "label": "short label",
      "content_type": "thirty_day_plan|reel_script|carousel|post|hashtags",
      "platform": "instagram|linkedin|facebook|x|youtube",
      "prompt_text": "ready-to-use prompt for content generation"
    }
  ]
}

Use all business info, assets, URLs, reference links, and social accounts provided. Be specific to this brand.
PROMPT;
    }
}
