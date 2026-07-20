<?php

namespace App\Application\Services\Brand;

use App\Models\Brand;
use App\Models\BrandAsset;
use Illuminate\Support\Facades\Storage;

class BrandWizardPersistenceService
{
    private const SOCIAL_LABELS = [
        'fb' => 'Facebook',
        'ig' => 'Instagram',
        'li' => 'LinkedIn',
        'x' => 'X / Twitter',
        'yt' => 'YouTube',
        'pi' => 'Pinterest',
        'th' => 'Threads',
    ];

    public function persistFromSession(Brand $brand): void
    {
        $this->persistUploadedFiles($brand);
        $this->persistLogo($brand);
        $this->persistUrlAssets($brand);
        $this->markSourcesUpdated($brand);
        $this->clearSession($brand);
    }

    public function syncSessionFiles(Brand $brand): void
    {
        $this->persistUploadedFiles($brand);
        $this->persistLogo($brand);
        $this->markSourcesUpdated($brand);
    }

    public function syncSessionUrls(Brand $brand): void
    {
        $this->persistUrlAssets($brand);
        $this->markSourcesUpdated($brand);
    }

    public function markSourcesUpdated(Brand $brand): void
    {
        $brand->forceFill(['sources_updated_at' => now()])->save();
    }

    private function persistUploadedFiles(Brand $brand): void
    {
        foreach (session("brand_{$brand->id}_assets", []) as $asset) {
            $path = $asset['path'] ?? null;

            if (! $path) {
                continue;
            }

            $fileName = $asset['name'] ?? basename($path);
            $mimeType = Storage::disk('local')->exists($path)
                ? (Storage::disk('local')->mimeType($path) ?: null)
                : null;

            BrandAsset::query()->updateOrCreate(
                [
                    'brand_id' => $brand->id,
                    'file_path' => $path,
                ],
                [
                    'file_name' => $fileName,
                    'disk' => 'local',
                    'file_type' => $this->detectFileType($fileName),
                    'mime_type' => $mimeType,
                    'file_size' => (int) ($asset['size'] ?? 0),
                    'status' => 'processing',
                    'metadata' => ['source' => 'wizard_step_2'],
                ]
            );
        }
    }

    private function persistLogo(Brand $brand): void
    {
        if (! $brand->logo_path) {
            return;
        }

        if (! Storage::disk('public')->exists($brand->logo_path)) {
            return;
        }

        BrandAsset::query()->updateOrCreate(
            [
                'brand_id' => $brand->id,
                'file_path' => $brand->logo_path,
            ],
            [
                'file_name' => 'Brand logo',
                'disk' => 'public',
                'file_type' => 'logo',
                'mime_type' => Storage::disk('public')->mimeType($brand->logo_path) ?: null,
                'file_size' => (int) (Storage::disk('public')->size($brand->logo_path) ?: 0),
                'status' => 'processing',
                'metadata' => ['source' => 'brand_create'],
            ]
        );
    }

    private function persistUrlAssets(Brand $brand): void
    {
        BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('disk', 'url')
            ->delete();

        if (filled($brand->website)) {
            $this->createUrlAsset($brand, 'Brand website', $brand->website, [
                'source' => 'wizard_step_3',
                'url_type' => 'website',
            ]);
        }

        foreach (session("brand_{$brand->id}_social_urls", []) as $platform => $url) {
            if (! filled($url)) {
                continue;
            }

            $this->createUrlAsset($brand, (self::SOCIAL_LABELS[$platform] ?? ucfirst($platform)).' URL', $url, [
                'source' => 'wizard_step_3',
                'url_type' => 'social',
                'platform' => $platform,
            ]);
        }

        foreach (session("brand_{$brand->id}_reference_urls", []) as $index => $url) {
            if (! filled($url)) {
                continue;
            }

            $this->createUrlAsset($brand, 'Reference URL '.($index + 1), $url, [
                'source' => 'wizard_step_4',
                'url_type' => 'reference',
                'index' => $index + 1,
            ]);
        }
    }

    private function createUrlAsset(Brand $brand, string $name, string $url, array $metadata): void
    {
        BrandAsset::query()->create([
            'brand_id' => $brand->id,
            'file_name' => $name,
            'file_path' => $url,
            'disk' => 'url',
            'file_type' => 'website',
            'file_size' => 0,
            'status' => 'processing',
            'metadata' => $metadata,
        ]);
    }

    private function clearSession(Brand $brand): void
    {
        session()->forget([
            "brand_{$brand->id}_assets",
            "brand_{$brand->id}_social_urls",
            "brand_{$brand->id}_reference_urls",
        ]);
    }

    private function detectFileType(string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'pdf',
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg' => 'image',
            'mp4', 'mov', 'webm' => 'video',
            'mp3', 'wav', 'aac' => 'audio',
            'docx' => 'docx',
            default => 'image',
        };
    }
}
