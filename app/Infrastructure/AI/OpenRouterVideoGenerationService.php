<?php

namespace App\Infrastructure\AI;

use App\Models\Brand;
use App\Models\BrandAsset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenRouterVideoGenerationService
{
    /** @return array{asset: BrandAsset, provider: string, model: string} */
    public function generateForBrand(Brand $brand, string $prompt, string $title = 'AI reel'): array
    {
        if (! config('services.openrouter.api_key')) {
            throw new RuntimeException('OpenRouter API key is not configured.');
        }

        $models = $this->models();
        $lastError = 'Video generation failed. Check your OpenRouter credits and try again.';

        foreach ($models as $model) {
            try {
                $binary = $this->requestVideo($model, $prompt);
                $asset = $this->storeVideo($brand, $binary, $title, $prompt, $model);

                return [
                    'asset' => $asset,
                    'provider' => 'openrouter',
                    'model' => $model,
                ];
            } catch (RuntimeException $e) {
                $lastError = $e->getMessage();

                if (! $this->shouldRetryWithNextModel($e->getMessage())) {
                    throw $e;
                }

                Log::warning('openrouter video model unavailable, trying fallback', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException($lastError);
    }

    /** @return list<string> */
    private function models(): array
    {
        $primary = (string) config('services.openrouter.video_model');
        $fallbacks = config('services.openrouter.video_fallback_models', []);

        if (! is_array($fallbacks)) {
            $fallbacks = [];
        }

        return array_values(array_unique(array_filter([$primary, ...$fallbacks])));
    }

    private function requestVideo(string $model, string $prompt): string
    {
        $duration = max(1, (int) config('services.openrouter.video_duration', 8));
        $generateAudio = filter_var(config('services.openrouter.video_generate_audio', true), FILTER_VALIDATE_BOOL);

        $submit = $this->httpClient()
            ->withToken((string) config('services.openrouter.api_key'))
            ->withHeaders($this->openRouterHeaders())
            ->timeout(120)
            ->post('https://openrouter.ai/api/v1/videos', [
                'model' => $model,
                'prompt' => Str::limit(trim($prompt), 4000, ''),
                'duration' => $duration,
                'resolution' => (string) config('services.openrouter.video_resolution', '720p'),
                'aspect_ratio' => (string) config('services.openrouter.video_aspect_ratio', '9:16'),
                'generate_audio' => $generateAudio,
            ]);

        if (! $submit->successful()) {
            $message = (string) ($submit->json('error.message') ?: $submit->json('message') ?: 'Video generation failed.');

            Log::warning('openrouter video submit failed', [
                'model' => $model,
                'status' => $submit->status(),
                'body' => $submit->body(),
            ]);

            throw new RuntimeException($message);
        }

        $jobId = (string) $submit->json('id');

        if ($jobId === '') {
            throw new RuntimeException('Video generation did not return a job ID.');
        }

        $pollUrl = (string) ($submit->json('polling_url') ?: "https://openrouter.ai/api/v1/videos/{$jobId}");
        $maxAttempts = max(12, (int) config('services.openrouter.video_poll_attempts', 120));
        $pollInterval = max(3, (int) config('services.openrouter.video_poll_interval', 5));

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($attempt > 0) {
                sleep($pollInterval);
            }

            $poll = $this->httpClient()
                ->withToken((string) config('services.openrouter.api_key'))
                ->withHeaders($this->openRouterHeaders())
                ->timeout(60)
                ->get($pollUrl);

            if (! $poll->successful()) {
                throw new RuntimeException((string) ($poll->json('error.message') ?: 'Could not poll video generation status.'));
            }

            $status = (string) $poll->json('status');

            if ($status === 'completed') {
                return $this->downloadCompletedVideo($jobId, $poll->json());
            }

            if (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                throw new RuntimeException((string) ($poll->json('error') ?: 'Video generation failed.'));
            }
        }

        throw new RuntimeException('Video generation timed out. Please try again.');
    }

    /** @param  array<string, mixed>  $pollPayload */
    private function downloadCompletedVideo(string $jobId, array $pollPayload): string
    {
        $urls = $pollPayload['unsigned_urls'] ?? [];
        $downloadUrl = is_array($urls) && isset($urls[0]) && is_string($urls[0])
            ? $urls[0]
            : "https://openrouter.ai/api/v1/videos/{$jobId}/content";

        $response = $this->httpClient()
            ->withToken((string) config('services.openrouter.api_key'))
            ->withHeaders($this->openRouterHeaders())
            ->timeout(180)
            ->get($downloadUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Could not download generated video.');
        }

        $binary = $response->body();

        if ($binary === '') {
            throw new RuntimeException('Generated video file was empty.');
        }

        return $binary;
    }

    private function storeVideo(
        Brand $brand,
        string $binary,
        string $title,
        string $prompt,
        string $model,
    ): BrandAsset {
        $fileName = Str::slug(Str::limit($title, 60, '')) ?: 'ai-reel';
        $path = "brands/{$brand->id}/content_library/ai-videos/{$fileName}-".Str::uuid().'.mp4';

        Storage::disk('local')->put($path, $binary);

        return BrandAsset::query()->create([
            'brand_id' => $brand->id,
            'file_name' => $fileName.'.mp4',
            'file_path' => $path,
            'disk' => 'local',
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size' => strlen($binary),
            'status' => 'indexed',
            'indexed_at' => now(),
            'metadata' => [
                'source' => 'content_library',
                'library_type' => 'reel',
                'from_content_suggestion' => true,
                'generation_prompt' => $prompt,
                'ai_provider' => 'openrouter',
                'ai_model' => $model,
                'duration_seconds' => (int) config('services.openrouter.video_duration', 8),
                'generate_audio' => filter_var(config('services.openrouter.video_generate_audio', true), FILTER_VALIDATE_BOOL),
            ],
        ]);
    }

    /** @return array<string, string> */
    private function openRouterHeaders(): array
    {
        return [
            'HTTP-Referer' => (string) config('services.openrouter.referer', config('app.url')),
            'X-Title' => (string) config('services.openrouter.title', config('app.name')),
        ];
    }

    private function shouldRetryWithNextModel(string $message): bool
    {
        $needles = [
            'no endpoints found',
            'model not found',
            'does not exist',
            'invalid model',
            'not found',
            'unavailable',
            'rate limit',
            'quota exceeded',
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
