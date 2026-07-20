<?php

namespace App\Application\Services\Brand;

use App\Infrastructure\AI\KnowledgeBaseTrainerInterface;
use App\Models\AiSuggestedPrompt;
use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\BrandKnowledgeBase;
use Illuminate\Support\Facades\Log;

class BrandKnowledgeBaseService
{
    public function __construct(private readonly KnowledgeBaseTrainerInterface $trainer)
    {
    }

    public function trainSafely(Brand $brand): bool
    {
        try {
            $this->train($brand);

            return true;
        } catch (\Throwable $e) {
            Log::error('Brand knowledge base training failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function ensureTrained(Brand $brand): void
    {
        $brand->loadMissing('knowledgeBase');

        $status = $brand->knowledgeBase?->training_status;
        $hasAiAnalysis = filled(data_get($brand->knowledgeBase?->source_data, 'ai_analysis'));

        if ($brand->isSetupComplete() && (in_array($status, [null, 'idle', 'failed'], true) || ($status === 'complete' && ! $hasAiAnalysis))) {
            $this->trainSafely($brand);
        }
    }

    public function forceRetrain(Brand $brand): bool
    {
        return $this->trainSafely($brand);
    }

    public function train(Brand $brand): void
    {
        $brand->load(['voiceSettings', 'assets', 'socialAccounts', 'knowledgeBase']);

        $knowledgeBase = $brand->knowledgeBase ?? BrandKnowledgeBase::query()->create(['brand_id' => $brand->id]);

        $knowledgeBase->update([
            'training_status' => 'processing',
            'training_error' => null,
        ]);

        try {
            $voice = $brand->voiceSettings;
            $sourceData = $this->buildSourceData($brand, $voice);

            $analysis = $this->trainer->analyze($sourceData);
            $sourceData['ai_analysis'] = $analysis;

            $knowledgeBase->update([
                'detected_tone' => $analysis['detected_tone'] ?? $brand->tone,
                'detected_audience' => $analysis['detected_audience'] ?? $voice?->target_audience,
                'detected_services' => $analysis['detected_services'] ?? $voice?->products_services,
                'top_keywords' => $analysis['top_keywords'] ?? $voice?->keywords,
                'source_data' => $sourceData,
                'training_status' => 'complete',
                'last_trained_at' => now(),
                'training_error' => null,
            ]);

            $this->syncSuggestedPrompts($brand, $analysis['suggested_prompts'] ?? []);

            BrandAsset::query()
                ->where('brand_id', $brand->id)
                ->whereIn('status', ['uploading', 'processing'])
                ->update([
                    'status' => 'indexed',
                    'indexed_at' => now(),
                ]);

            Log::info('Brand knowledge base sent to AI', [
                'brand_id' => $brand->id,
                'provider' => $analysis['provider'] ?? 'unknown',
                'assets' => count($sourceData['assets'] ?? []),
            ]);
        } catch (\Throwable $e) {
            $knowledgeBase->update([
                'training_status' => 'failed',
                'training_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function syncSuggestedPrompts(Brand $brand, array $prompts): void
    {
        AiSuggestedPrompt::query()->where('brand_id', $brand->id)->delete();

        foreach (array_values($prompts) as $index => $prompt) {
            if (! is_array($prompt) || empty($prompt['prompt_text'])) {
                continue;
            }

            AiSuggestedPrompt::query()->create([
                'brand_id' => $brand->id,
                'content_type' => $prompt['content_type'] ?? 'post',
                'platform' => $prompt['platform'] ?? null,
                'label' => $prompt['label'] ?? 'Suggested prompt',
                'prompt_text' => $prompt['prompt_text'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    private function buildSourceData(Brand $brand, $voice): array
    {
        $urlAssets = $brand->assets->where('disk', 'url');

        return [
            'business' => [
                'name' => $brand->name,
                'website' => $brand->website,
                'industry' => $brand->industry,
                'country' => $brand->country,
                'language' => $brand->language,
                'tone' => $brand->tone,
                'description' => $voice?->company_description ?? $brand->short_description,
                'products_services' => $voice?->products_services,
                'target_audience' => $voice?->target_audience,
                'keywords' => $voice?->keywords,
            ],
            'assets' => $brand->assets
                ->where('disk', '!=', 'url')
                ->map(fn (BrandAsset $asset) => [
                    'id' => $asset->id,
                    'name' => $asset->file_name,
                    'type' => $asset->file_type,
                    'path' => $asset->file_path,
                    'disk' => $asset->disk,
                    'metadata' => $asset->metadata,
                ])->values()->all(),
            'urls' => [
                'website' => $brand->website,
                'social' => $urlAssets
                    ->filter(fn (BrandAsset $a) => ($a->metadata['url_type'] ?? '') === 'social')
                    ->map(fn (BrandAsset $a) => [
                        'platform' => $a->metadata['platform'] ?? null,
                        'label' => $a->file_name,
                        'url' => $a->file_path,
                    ])->values()->all(),
                'reference' => $urlAssets
                    ->filter(fn (BrandAsset $a) => ($a->metadata['url_type'] ?? '') === 'reference')
                    ->map(fn (BrandAsset $a) => [
                        'label' => $a->file_name,
                        'url' => $a->file_path,
                    ])->values()->all(),
            ],
            'social_accounts' => $brand->socialAccounts->map(fn ($account) => [
                'platform' => $account->platform,
                'name' => $account->account_name,
                'handle' => $account->account_handle,
                'status' => $account->status,
            ])->values()->all(),
            'trained_at' => now()->toIso8601String(),
        ];
    }
}
