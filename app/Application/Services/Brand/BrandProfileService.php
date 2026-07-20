<?php

namespace App\Application\Services\Brand;

use App\Models\Brand;

class BrandProfileService
{
    public function build(Brand $brand): array
    {
        $voice = $brand->voiceSettings;
        $keywords = is_array($voice?->keywords) ? implode(', ', $voice->keywords) : '';

        $fileAssets = $brand->relationLoaded('assets')
            ? $brand->assets->where('disk', '!=', 'url')
            : $brand->assets()->where('disk', '!=', 'url')->get();

        $assets = $fileAssets->map(function ($asset) {
            $kind = $this->assetKind(
                (string) $asset->file_type,
                (string) $asset->file_name,
                (string) ($asset->mime_type ?? ''),
            );

            return [
                'id' => $asset->id,
                'name' => $asset->file_name,
                'size' => $this->formatBytes((int) $asset->file_size),
                'type' => $asset->file_type,
                'kind' => $kind,
                'url' => route('app.brand.assets.show', $asset),
            ];
        })->values()->all();

        if ($assets === []) {
            $assets = collect(session("brand_{$brand->id}_assets", []))
                ->map(function (array $asset) {
                    $name = (string) ($asset['name'] ?? 'file');

                    return [
                        'id' => null,
                        'name' => $name,
                        'size' => $this->formatBytes($asset['size'] ?? 0),
                        'type' => $asset['type'] ?? null,
                        'kind' => $this->assetKind((string) ($asset['type'] ?? ''), $name, ''),
                        'url' => null,
                    ];
                })->all();
        }

        $socialLabels = [
            'fb' => 'Facebook',
            'ig' => 'Instagram',
            'li' => 'LinkedIn',
            'x' => 'X / Twitter',
            'yt' => 'YouTube',
            'pi' => 'Pinterest',
            'th' => 'Threads',
        ];

        $urlAssets = $brand->relationLoaded('assets')
            ? $brand->assets->where('disk', 'url')
            : $brand->assets()->where('disk', 'url')->get();

        $socialUrls = $urlAssets
            ->filter(fn ($asset) => ($asset->metadata['url_type'] ?? '') === 'social')
            ->map(fn ($asset) => [
                'label' => $socialLabels[$asset->metadata['platform'] ?? ''] ?? $asset->file_name,
                'url' => $asset->file_path,
                'platform' => $asset->metadata['platform'] ?? null,
            ])
            ->values()
            ->all();

        if ($socialUrls === []) {
            $socialUrls = collect(session("brand_{$brand->id}_social_urls", []))
                ->map(fn (string $url, string $key) => [
                    'label' => $socialLabels[$key] ?? ucfirst($key),
                    'url' => $url,
                    'platform' => $key,
                ])
                ->values()
                ->all();
        }

        $referenceUrls = $urlAssets
            ->filter(fn ($asset) => ($asset->metadata['url_type'] ?? '') === 'reference')
            ->sortBy(fn ($asset) => $asset->metadata['index'] ?? 0)
            ->pluck('file_path')
            ->values()
            ->all();

        if ($referenceUrls === []) {
            $referenceUrls = collect(session("brand_{$brand->id}_reference_urls", []))
                ->filter(fn ($url) => filled($url))
                ->values()
                ->all();
        }

        return [
            'knowledge_base' => $brand->knowledgeBase,
            'ai_analysis' => data_get($brand->knowledgeBase?->source_data, 'ai_analysis', []),
            'suggested_prompts' => $brand->suggestedPrompts,
            'step1' => [
                'title' => 'Business information',
                'fields' => array_filter([
                    ['label' => 'Company name', 'value' => $brand->name],
                    ['label' => 'Website URL', 'value' => $brand->website],
                    ['label' => 'Company description', 'value' => $voice?->company_description ?? $brand->short_description],
                    ['label' => 'Products / services', 'value' => $voice?->products_services],
                    ['label' => 'Target audience', 'value' => $voice?->target_audience],
                    ['label' => 'Brand tone', 'value' => $brand->tone],
                    ['label' => 'Primary language', 'value' => $brand->language],
                    ['label' => 'Industry', 'value' => $brand->industry],
                    ['label' => 'Country', 'value' => $brand->country],
                    ['label' => 'Keywords & hashtags', 'value' => $keywords],
                ], fn (array $field) => filled($field['value'] ?? null)),
            ],
            'step2' => [
                'title' => 'Brand assets',
                'assets' => $assets,
            ],
            'step3' => [
                'title' => 'Brand Url',
                'website' => $brand->website,
                'social_urls' => $socialUrls,
            ],
            'step4' => [
                'title' => 'Reference Data url',
                'urls' => $referenceUrls,
            ],
            'step5' => [
                'title' => 'Social presence',
                'accounts' => $brand->socialAccounts,
            ],
        ];
    }

    private function assetKind(string $fileType, string $fileName, string $mimeType): string
    {
        $fileType = strtolower($fileType);
        $mimeType = strtolower($mimeType);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileType, ['image', 'logo'], true) || str_starts_with($mimeType, 'image/') || in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)) {
            return 'image';
        }

        if ($fileType === 'pdf' || $mimeType === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }

        if ($fileType === 'video' || str_starts_with($mimeType, 'video/') || in_array($ext, ['mp4', 'mov', 'webm'], true)) {
            return 'video';
        }

        if ($fileType === 'audio' || str_starts_with($mimeType, 'audio/') || in_array($ext, ['mp3', 'wav', 'aac'], true)) {
            return 'audio';
        }

        return 'other';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }
}
