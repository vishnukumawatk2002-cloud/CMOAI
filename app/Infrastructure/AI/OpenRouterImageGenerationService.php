<?php

namespace App\Infrastructure\AI;

use App\Models\Brand;
use App\Models\BrandAsset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenRouterImageGenerationService
{
    /** @return array{asset: BrandAsset, provider: string, model: string} */
    public function generateForBrand(Brand $brand, string $prompt, string $title = 'AI image', array $metadataExtra = []): array
    {
        if (! config('services.openrouter.api_key')) {
            throw new RuntimeException('OpenRouter API key is not configured.');
        }

        $models = $this->models();
        $lastError = 'Image generation failed. Check your OpenRouter credits and try again.';

        foreach ($models as $model) {
            try {
                $binary = $this->requestImage($model, $prompt);

                if ($binary === null) {
                    continue;
                }

                $asset = $this->storeImage($brand, $binary, $title, $prompt, $model, $metadataExtra);

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

                Log::warning('openrouter image model unavailable, trying fallback', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException($lastError);
    }

    /**
     * @param  list<string>  $slidePrompts
     * @return list<array{asset: BrandAsset, provider: string, model: string}>
     */
    public function generateCarouselForBrand(Brand $brand, array $slidePrompts, string $title = 'AI carousel'): array
    {
        $slideCount = max(1, (int) config('services.openrouter.carousel_slides', 4));
        $groupId = (string) Str::uuid();
        $prompts = array_values(array_filter(array_map('trim', $slidePrompts)));

        while (count($prompts) < $slideCount) {
            $prompts[] = $prompts[count($prompts) - 1] ?? "Instagram carousel slide for {$title}";
        }

        $prompts = array_slice($prompts, 0, $slideCount);
        $generated = [];

        foreach ($prompts as $slot => $prompt) {
            $generated[] = $this->generateForBrand(
                $brand,
                $prompt,
                "{$title}-slide-".($slot + 1),
                [
                    'library_type' => 'carousel',
                    'carousel' => true,
                    'carousel_group' => $groupId,
                    'slot' => $slot,
                ],
            );
        }

        return $generated;
    }

    /** @return list<string> */
    private function models(): array
    {
        $primary = (string) config('services.openrouter.image_model');
        $fallbacks = config('services.openrouter.image_fallback_models', []);

        if (! is_array($fallbacks)) {
            $fallbacks = [];
        }

        return array_values(array_unique(array_filter([$primary, ...$fallbacks])));
    }

    private function requestImage(string $model, string $prompt): ?string
    {
        $response = $this->httpClient()
            ->withToken((string) config('services.openrouter.api_key'))
            ->withHeaders([
                'HTTP-Referer' => (string) config('services.openrouter.referer', config('app.url')),
                'X-Title' => (string) config('services.openrouter.title', config('app.name')),
            ])
            ->timeout(180)
            ->post('https://openrouter.ai/api/v1/images', [
                'model' => $model,
                'prompt' => Str::limit(trim($prompt), 4000, ''),
                'aspect_ratio' => (string) config('services.openrouter.image_aspect_ratio', '1:1'),
                'output_format' => 'jpeg',
            ]);

        if (! $response->successful()) {
            $message = (string) ($response->json('error.message') ?: 'Image generation failed.');

            Log::warning('openrouter image generation failed', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException($message);
        }

        $encoded = $response->json('data.0.b64_json');

        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $binary = base64_decode($encoded, true);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Image generation returned invalid image data.');
        }

        return $binary;
    }

    private function storeImage(
        Brand $brand,
        string $binary,
        string $title,
        string $prompt,
        string $model,
        array $metadataExtra = [],
    ): BrandAsset {
        $fileName = Str::slug(Str::limit($title, 60, '')) ?: 'ai-image';
        $path = "brands/{$brand->id}/content_library/ai-images/{$fileName}-".Str::uuid().'.jpg';

        Storage::disk('local')->put($path, $binary);

        return BrandAsset::query()->create([
            'brand_id' => $brand->id,
            'file_name' => $fileName.'.jpg',
            'file_path' => $path,
            'disk' => 'local',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($binary),
            'status' => 'indexed',
            'indexed_at' => now(),
            'metadata' => array_merge([
                'source' => 'content_library',
                'library_type' => 'image',
                'from_content_suggestion' => true,
                'generation_prompt' => $prompt,
                'ai_provider' => 'openrouter',
                'ai_model' => $model,
            ], $metadataExtra),
        ]);
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
