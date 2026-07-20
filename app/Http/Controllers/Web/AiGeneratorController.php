<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BrandAsset;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiGeneratorController extends Controller
{
    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $brand->load(['assets', 'socialAccounts', 'voiceSettings', 'suggestedPrompts']);

        $urlAssets = $brand->assets->where('disk', 'url');
        $fileAssets = $brand->assets->where('disk', '!=', 'url');

        $linkSources = collect();

        if ($brand->website) {
            $linkSources->push([
                'id' => 'website',
                'name' => 'Brand Website',
                'sub' => parse_url($brand->website, PHP_URL_HOST) ?: $brand->website,
                'icon' => 'ti-world',
                'color' => '#3B82F6',
            ]);
        }

        foreach ($urlAssets->filter(fn ($a) => ($a->metadata['url_type'] ?? '') === 'social') as $asset) {
            $platform = $asset->metadata['platform'] ?? 'social';
            $linkSources->push([
                'id' => 'url-'.$asset->id,
                'name' => $asset->file_name,
                'sub' => $asset->file_path,
                'icon' => match ($platform) {
                    'fb', 'facebook' => 'ti-brand-facebook',
                    'ig', 'instagram' => 'ti-brand-instagram',
                    'li', 'linkedin' => 'ti-brand-linkedin',
                    'x', 'twitter' => 'ti-brand-x',
                    'yt', 'youtube' => 'ti-brand-youtube',
                    default => 'ti-link',
                },
                'color' => '#5B4FC9',
            ]);
        }

        $photoAssets = $fileAssets->filter(fn (BrandAsset $a) => str_starts_with((string) $a->file_type, 'image'));
        $videoAssets = $fileAssets->filter(fn (BrandAsset $a) => str_starts_with((string) $a->file_type, 'video'));
        $audioAssets = $fileAssets->filter(fn (BrandAsset $a) => str_starts_with((string) $a->file_type, 'audio'));

        return view('app.ai-generator.index', [
            'brand' => $brand,
            'linkSources' => $linkSources,
            'photoAssets' => $photoAssets,
            'videoAssets' => $videoAssets,
            'audioAssets' => $audioAssets,
            'defaultAudience' => $brand->voiceSettings?->target_audience ?? '',
            'defaultTone' => $brand->tone ?? 'Professional',
        ]);
    }
}
