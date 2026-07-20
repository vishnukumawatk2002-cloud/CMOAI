<?php

namespace App\Application\Services\Brand;

use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\ContentItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandContentLibraryService
{
    /** @var array<string, list<string>> */
    private const CATEGORY_TYPES = [
        'caption' => ['image_caption', 'hashtags', 'thread', 'post', 'thirty_day_plan'],
        'image' => [],
        'reel' => ['reel_script'],
        'carousel' => ['carousel'],
    ];

    /** @var array<string, array{title: string, icon: string, color: string, bg: string}> */
    private const CATEGORY_META = [
        'caption' => [
            'title' => 'Caption',
            'icon' => 'ti-align-left',
            'color' => '#5B4FC9',
            'bg' => '#EEF0FF',
        ],
        'image' => [
            'title' => 'Image',
            'icon' => 'ti-photo',
            'color' => '#EC4899',
            'bg' => '#FDF2F8',
        ],
        'reel' => [
            'title' => 'Reels/Shorts',
            'icon' => 'ti-player-play',
            'color' => '#EF4444',
            'bg' => '#FEF2F2',
        ],
        'carousel' => [
            'title' => 'Carousel Images',
            'icon' => 'ti-layout-columns',
            'color' => '#16A34A',
            'bg' => '#F0FDF4',
        ],
    ];

    public function buildAiCategories(Brand $brand, array $aiAnalysis = []): array
    {
        $contentItems = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('metadata->from_content_suggestion', true)
            ->where(function ($query) {
                $query->whereNull('metadata->from_post_planning')
                    ->orWhere('metadata->from_post_planning', false);
            })
            ->latest()
            ->get();

        $categories = [];

        foreach (self::CATEGORY_META as $key => $meta) {
            $items = $this->renumberItems(
                $this->mapContentItems($contentItems, $key)->values()->all()
            );

            $categories[] = array_merge($meta, [
                'key' => $key,
                'items' => $items,
            ]);
        }

        return $categories;
    }

    public function buildPlanningCategories(Brand $brand, array $aiAnalysis = []): array
    {
        $manualByKey = collect($this->buildManualCategories($brand, 'content_library'))->keyBy('key');
        $aiByKey = collect($this->buildAiCategories($brand, $aiAnalysis))->keyBy('key');

        $categories = [];

        foreach (self::CATEGORY_META as $key => $meta) {
            $manualItems = $manualByKey->get($key)['items'] ?? [];
            $aiItems = $aiByKey->get($key)['items'] ?? [];

            $categories[] = array_merge($meta, [
                'key' => $key,
                'items' => $this->renumberItems(array_merge($aiItems, $manualItems)),
            ]);
        }

        return $categories;
    }

    public function buildManualCategories(Brand $brand, string $section = 'content_library'): array
    {
        $assets = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('disk', '!=', 'url')
            ->where(function ($query) {
                $query->whereNull('metadata->from_content_suggestion')
                    ->orWhere('metadata->from_content_suggestion', false);
            })
            ->where('file_path', 'not like', '%/ai-images/%')
            ->where('file_path', 'not like', '%/ai-videos/%')
            ->where(function ($query) use ($section) {
                if ($section === 'content_library') {
                    $query->where('metadata->source', 'content_library')
                        ->orWhere(function ($inner) {
                            $inner->whereNull('metadata->source')
                                ->whereNotNull('metadata->library_type');
                        });
                } else {
                    $query->where('metadata->source', $section);
                }
            })
            ->latest()
            ->get();

        $buckets = collect(self::CATEGORY_META)->mapWithKeys(fn ($meta, $key) => [$key => collect()]);

        foreach ($assets as $asset) {
            $placed = false;

            foreach (array_keys(self::CATEGORY_META) as $key) {
                if ($this->matchesManualCategory($asset, $key)) {
                    $buckets[$key]->push($asset);
                    $placed = true;
                    break;
                }
            }

            if (! $placed) {
                $fallback = str_starts_with((string) $asset->file_type, 'image')
                    || str_starts_with((string) $asset->mime_type, 'image/')
                    ? 'image'
                    : 'caption';
                $buckets[$fallback]->push($asset);
            }
        }

        $categories = [];

        foreach (self::CATEGORY_META as $key => $meta) {
            $items = $this->renumberItems(
                $buckets[$key]->map(fn (BrandAsset $asset, int $index) => $this->mapManualAsset($asset, $index + 1))->values()->all()
            );

            if ($key === 'caption') {
                $categories[] = array_merge($meta, [
                    'key' => $key,
                    'items' => $this->buildCaptionPostItems($buckets[$key], $assets),
                ]);

                continue;
            }

            if ($key === 'carousel') {
                $categories[] = array_merge($meta, [
                    'key' => $key,
                    'items' => $this->buildManualCarouselItems($buckets[$key]),
                ]);

                continue;
            }

            if ($key === 'image') {
                $categories[] = array_merge($meta, [
                    'key' => $key,
                    'items' => $this->buildManualImageItems($buckets[$key]),
                ]);

                continue;
            }

            if ($key === 'reel') {
                $categories[] = array_merge($meta, [
                    'key' => $key,
                    'items' => $this->buildManualReelItems($buckets[$key]),
                ]);

                continue;
            }

            $items = $this->finalizeVisualCategory($items, $brand, $key, 'manual');

            $categories[] = array_merge($meta, [
                'key' => $key,
                'items' => $items,
            ]);
        }

        return $categories;
    }

    private function mapContentItems(Collection $items, string $categoryKey): Collection
    {
        $filtered = $items->filter(function (ContentItem $item) use ($categoryKey) {
            return data_get($item->metadata, 'suggestion_category') === $categoryKey;
        });

        $assetIds = $filtered
            ->flatMap(function (ContentItem $item) {
                $ids = data_get($item->metadata, 'carousel_asset_ids', []);

                if (is_array($ids) && $ids !== []) {
                    return $ids;
                }

                $single = data_get($item->metadata, 'asset_id');

                return $single ? [$single] : [];
            })
            ->filter()
            ->unique()
            ->all();

        $assets = BrandAsset::query()
            ->whereIn('id', $assetIds)
            ->get()
            ->keyBy('id');

        return $filtered->map(function (ContentItem $item) use ($categoryKey, $assets) {
            $asset = $assets->get(data_get($item->metadata, 'asset_id'));
            $mapped = [
                'number' => 0,
                'title' => filled($item->title)
                    ? $item->title
                    : ucfirst(str_replace('_', ' ', $item->content_type ?? 'post')),
                'text' => $item->body,
                'tags' => array_values(array_filter([
                    $item->platform,
                    data_get($item->metadata, 'ai_provider'),
                    str_replace('_', ' ', $item->content_type ?? 'post'),
                ])),
                'source' => 'generated',
                'platform' => $item->platform,
                'content_item_id' => $item->id,
            ];

            if ($categoryKey === 'image' && $asset instanceof BrandAsset) {
                $mapped['image_url'] = route('app.brand.assets.show', $asset);
                $mapped['asset_id'] = $asset->id;
                $mapped['text'] = '';
            }

            if ($categoryKey === 'carousel') {
                $carouselAssetIds = data_get($item->metadata, 'carousel_asset_ids', []);
                $carouselAssets = collect(is_array($carouselAssetIds) ? $carouselAssetIds : [])
                    ->map(fn ($id) => $assets->get($id))
                    ->filter()
                    ->values();

                if ($carouselAssets->isEmpty() && $asset instanceof BrandAsset) {
                    $carouselAssets = collect([$asset]);
                }

                $sorted = $carouselAssets
                    ->sortBy(fn (BrandAsset $carouselAsset) => (int) data_get($carouselAsset->metadata, 'slot', 999))
                    ->values();

                $slots = array_fill(0, 4, null);
                foreach ($sorted as $carouselAsset) {
                    $slot = (int) data_get($carouselAsset->metadata, 'slot', -1);
                    if ($slot >= 0 && $slot < 4 && $slots[$slot] === null) {
                        $slots[$slot] = $carouselAsset;

                        continue;
                    }

                    for ($i = 0; $i < 4; $i++) {
                        if ($slots[$i] === null) {
                            $slots[$i] = $carouselAsset;
                            break;
                        }
                    }
                }

                $previews = [];
                for ($i = 0; $i < 4; $i++) {
                    /** @var BrandAsset|null $slotAsset */
                    $slotAsset = $slots[$i];
                    $previews[] = $slotAsset instanceof BrandAsset ? [
                        'url' => route('app.brand.assets.show', $slotAsset),
                        'label' => 'Slide '.($i + 1),
                        'asset_id' => $slotAsset->id,
                        'slot' => $i,
                    ] : [
                        'url' => null,
                        'label' => 'Slide '.($i + 1),
                        'asset_id' => null,
                        'slot' => $i,
                    ];
                }

                $mapped['preview_images'] = $previews;
                $mapped['text'] = '';
            }

            if ($categoryKey === 'reel' && $asset instanceof BrandAsset) {
                $mapped['reel_preview'] = true;
                $mapped['video_url'] = route('app.brand.assets.show', $asset);
                $mapped['asset_id'] = $asset->id;
                $mapped['text'] = '';
            }

            return $mapped;
        });
    }

    private function mapManualAsset(BrandAsset $asset, int $number): array
    {
        $type = $asset->file_type ?? 'file';
        $libraryType = data_get($asset->metadata, 'library_type');

        if ($libraryType === 'caption') {
            return [
                'number' => $number,
                'title' => 'Caption '.$number,
                'text' => (string) data_get($asset->metadata, 'caption_text', ''),
                'tags' => ['caption'],
                'source' => 'manual',
                'asset_id' => $asset->id,
            ];
        }

        return [
            'number' => $number,
            'title' => $asset->file_name,
            'text' => $this->manualAssetDescription($asset),
            'tags' => array_values(array_filter([$type, $asset->status])),
            'source' => 'manual',
            'asset_id' => $asset->id,
            'file_path' => $asset->file_path,
            'mime_type' => $asset->mime_type,
            'is_image' => str_starts_with((string) $type, 'image') || str_starts_with((string) $asset->mime_type, 'image/'),
            'is_video' => str_starts_with((string) $type, 'video') || str_starts_with((string) $asset->mime_type, 'video/'),
        ];
    }

    private function manualAssetDescription(BrandAsset $asset): string
    {
        $size = $asset->file_size ? number_format($asset->file_size / 1024, 1).' KB' : 'Uploaded file';

        return trim(($asset->mime_type ?: ucfirst($asset->file_type ?? 'file')).' · '.$size);
    }

    private function matchesManualCategory(BrandAsset $asset, string $category): bool
    {
        $libraryType = data_get($asset->metadata, 'library_type');

        if (filled($libraryType)) {
            return match ($category) {
                'caption' => $libraryType === 'caption',
                'image' => $libraryType === 'image',
                'reel' => $libraryType === 'reel',
                'carousel' => $libraryType === 'carousel',
                default => false,
            };
        }

        return false;
    }

    /** @param \Illuminate\Support\Collection<int, BrandAsset> $assets @param \Illuminate\Support\Collection<int, BrandAsset> $allAssets */
    private function buildCaptionPostItems(Collection $assets, Collection $allAssets): array
    {
        $assetById = $allAssets->keyBy('id');

        $groups = $assets->groupBy(fn (BrandAsset $asset) => data_get($asset->metadata, 'content_group', 'legacy-'.$asset->id));

        return $this->renumberItems($groups->map(function (Collection $groupAssets) use ($assetById) {
            $first = $groupAssets->first();

            $captionAsset = $groupAssets->first(fn (BrandAsset $asset) => data_get($asset->metadata, 'role') === 'caption'
                || filled(data_get($asset->metadata, 'caption_text'))
                || data_get($asset->metadata, 'library_type') === 'caption');

            if (! $captionAsset) {
                $captionAsset = $groupAssets->first(fn (BrandAsset $asset) => in_array($asset->file_type, ['guidelines', 'text'], true))
                    ?? $first;
            }

            $imageAsset = $groupAssets->first(fn (BrandAsset $asset) => data_get($asset->metadata, 'role') === 'image'
                || ($asset->file_type === 'image' && data_get($asset->metadata, 'library_type') === 'caption'));

            if (! $imageAsset && $captionAsset) {
                $pairedId = data_get($captionAsset->metadata, 'paired_image_asset_id');
                if ($pairedId) {
                    $imageAsset = $assetById->get($pairedId);
                }
            }

            $captionText = (string) data_get($captionAsset?->metadata, 'caption_text', '');

            $contentGroup = (string) (data_get($captionAsset?->metadata, 'content_group')
                ?: data_get($imageAsset?->metadata, 'content_group')
                ?: data_get($first?->metadata, 'content_group', ''));

            $manualKey = (filled($contentGroup) && $contentGroup !== 'legacy-' && Str::isUuid($contentGroup))
                ? $contentGroup
                : (string) ($captionAsset?->id ?? $imageAsset?->id ?? $first?->id);

            return [
                'number' => 0,
                'title' => 'Caption post',
                'text' => $captionText,
                'tags' => ['caption'],
                'source' => 'manual',
                'manual_type' => 'caption',
                'manual_key' => $manualKey,
                'caption_asset_id' => $captionAsset?->id,
                'image_asset_id' => $imageAsset?->id,
                'caption_image_url' => $imageAsset instanceof BrandAsset
                    ? route('app.brand.assets.show', $imageAsset)
                    : null,
            ];
        })->values()->all());
    }

    /** @param \Illuminate\Support\Collection<int, BrandAsset> $assets */
    private function buildManualImageItems(Collection $assets): array
    {
        return $this->renumberItems($assets->map(function (BrandAsset $asset) {
            return [
                'number' => 0,
                'title' => $asset->file_name,
                'text' => '',
                'tags' => [],
                'source' => 'manual',
                'manual_type' => 'image',
                'manual_key' => (string) $asset->id,
                'asset_id' => $asset->id,
                'image_url' => route('app.brand.assets.show', $asset),
            ];
        })->values()->all());
    }

    /** @param \Illuminate\Support\Collection<int, BrandAsset> $assets */
    private function buildManualReelItems(Collection $assets): array
    {
        return $this->renumberItems($assets->map(function (BrandAsset $asset) {
            return [
                'number' => 0,
                'title' => $asset->file_name,
                'text' => '',
                'tags' => [],
                'source' => 'manual',
                'manual_type' => 'reel',
                'manual_key' => (string) $asset->id,
                'asset_id' => $asset->id,
                'reel_preview' => true,
                'video_url' => route('app.brand.assets.show', $asset),
            ];
        })->values()->all());
    }

    /** @param \Illuminate\Support\Collection<int, BrandAsset> $assets */
    private function buildManualCarouselItems(Collection $assets): array
    {
        $groups = $assets->groupBy(fn (BrandAsset $asset) => data_get($asset->metadata, 'carousel_group', 'legacy-'.$asset->id));

        return $this->renumberItems($groups->map(function (Collection $groupAssets) {
            $sorted = $groupAssets->sortBy(fn (BrandAsset $asset) => (int) data_get($asset->metadata, 'slot', 999))->values();
            $first = $sorted->first();
            $groupKey = (string) (data_get($first?->metadata, 'carousel_group') ?? 'legacy-'.$first?->id);

            $slots = array_fill(0, 4, null);
            foreach ($sorted as $asset) {
                $slot = (int) data_get($asset->metadata, 'slot', -1);
                if ($slot >= 0 && $slot < 4 && $slots[$slot] === null) {
                    $slots[$slot] = $asset;

                    continue;
                }

                for ($i = 0; $i < 4; $i++) {
                    if ($slots[$i] === null) {
                        $slots[$i] = $asset;
                        break;
                    }
                }
            }

            $previews = [];
            for ($i = 0; $i < 4; $i++) {
                /** @var BrandAsset|null $asset */
                $asset = $slots[$i];
                $previews[] = $asset instanceof BrandAsset ? [
                    'url' => route('app.brand.assets.show', $asset),
                    'label' => $asset->file_name,
                    'asset_id' => $asset->id,
                    'slot' => $i,
                ] : [
                    'url' => null,
                    'label' => 'Slide '.($i + 1),
                    'asset_id' => null,
                    'slot' => $i,
                ];
            }

            return [
                'number' => 0,
                'title' => $first?->file_name ?? 'Carousel post',
                'text' => '',
                'tags' => [],
                'source' => 'manual',
                'manual_type' => 'carousel',
                'manual_key' => $groupKey,
                'preview_images' => $previews,
            ];
        })->values()->all());
    }

    public function deleteCarouselSlot(Brand $brand, int $assetId): void
    {
        $asset = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('id', $assetId)
            ->firstOrFail();

        $this->deleteAssetFile($asset);
    }

    public function deleteAiContentItem(Brand $brand, int $contentItemId): bool
    {
        $item = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->whereKey($contentItemId)
            ->first();

        if (! $item instanceof ContentItem || ! data_get($item->metadata, 'from_content_suggestion')) {
            return false;
        }

        $assetIds = collect(is_array(data_get($item->metadata, 'carousel_asset_ids'))
            ? data_get($item->metadata, 'carousel_asset_ids')
            : []);

        $singleAssetId = data_get($item->metadata, 'asset_id');
        if ($singleAssetId) {
            $assetIds->push($singleAssetId);
        }

        BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->whereIn('id', $assetIds->filter()->unique()->all())
            ->get()
            ->each(fn (BrandAsset $asset) => $this->deleteAssetFile($asset));

        $item->delete();

        return true;
    }

    public function deleteManualItem(Brand $brand, string $type, string $key, string $section = 'content_library'): bool
    {
        $assets = $this->manualAssetsForKey($brand, $type, $key, $section);

        if ($assets->isEmpty()) {
            return false;
        }

        $deletedIds = $assets->pluck('id');

        foreach ($assets as $asset) {
            $this->deleteAssetFile($asset);
        }

        if ($type === 'caption') {
            $pairedIds = $assets
                ->map(fn (BrandAsset $asset) => data_get($asset->metadata, 'paired_image_asset_id'))
                ->filter()
                ->unique()
                ->reject(fn ($id) => $deletedIds->contains($id))
                ->values();

            foreach ($pairedIds as $pairedId) {
                $paired = BrandAsset::query()
                    ->where('brand_id', $brand->id)
                    ->where('id', $pairedId)
                    ->first();

                if ($paired) {
                    $this->deleteAssetFile($paired);
                }
            }
        }

        return true;
    }

    public function updateManualItem(Brand $brand, string $type, string $key, array $data, ?UploadedFile $file = null, string $section = 'content_library'): void
    {
        match ($type) {
            'caption' => $this->updateCaptionPost($brand, $key, trim((string) ($data['caption'] ?? '')), $file, $section),
            'image' => $this->replaceAssetFile($brand, (int) $key, $file, 'image', null, $section),
            'reel' => $this->replaceAssetFile($brand, (int) $key, $file, 'reel', null, $section),
            'carousel' => $this->hasCarouselSlotUpdate($data, $file)
                ? $this->updateCarouselSlot($brand, $key, (int) $data['carousel_slot'], $file, $section)
                : $this->replaceCarouselGroup($brand, $key, $data['carousel_images'] ?? [], $section),
            default => abort(422),
        };
    }

    /** @param array<string, mixed> $data */
    private function hasCarouselSlotUpdate(array $data, ?UploadedFile $file): bool
    {
        return $file instanceof UploadedFile
            && $file->isValid()
            && is_numeric($data['carousel_slot'] ?? null);
    }

    /** @return \Illuminate\Support\Collection<int, BrandAsset> */
    private function manualAssetsForKey(Brand $brand, string $type, string $key, string $section = 'content_library'): Collection
    {
        return match ($type) {
            'caption' => $this->captionGroupAssets($brand, $key, $section),
            'carousel' => $this->carouselGroupAssets($brand, $key, $section),
            'image', 'reel' => BrandAsset::query()
                ->where('brand_id', $brand->id)
                ->where('metadata->source', $section)
                ->where('id', (int) $key)
                ->get(),
            default => collect(),
        };
    }

    /** @return \Illuminate\Support\Collection<int, BrandAsset> */
    private function captionGroupAssets(Brand $brand, string $key, string $section = 'content_library'): Collection
    {
        $query = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('metadata->source', $section);

        if (ctype_digit((string) $key)) {
            $asset = (clone $query)->where('id', (int) $key)->first();

            if (! $asset) {
                return collect();
            }

            $groupId = data_get($asset->metadata, 'content_group');

            if (filled($groupId) && $groupId !== 'legacy-') {
                $groupAssets = (clone $query)->where('metadata->content_group', $groupId)->get();

                if ($groupAssets->isNotEmpty()) {
                    return $groupAssets;
                }
            }

            return collect([$asset]);
        }

        if (Str::isUuid($key)) {
            return $query->where('metadata->content_group', $key)->get();
        }

        $byGroup = (clone $query)->where('metadata->content_group', $key)->get();
        if ($byGroup->isNotEmpty()) {
            return $byGroup;
        }

        if (str_starts_with($key, 'legacy-')) {
            $legacyId = (int) substr($key, 7);

            if ($legacyId > 0) {
                $asset = (clone $query)->where('id', $legacyId)->first();

                if ($asset) {
                    return collect([$asset]);
                }
            }
        }

        return collect();
    }

    /** @return \Illuminate\Support\Collection<int, BrandAsset> */
    private function carouselGroupAssets(Brand $brand, string $key, string $section = 'content_library'): Collection
    {
        $base = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('metadata->source', $section);

        if (Str::isUuid($key)) {
            return $base->where('metadata->carousel_group', $key)->get();
        }

        $byGroup = $base->where('metadata->carousel_group', $key)->get();
        if ($byGroup->isNotEmpty()) {
            return $byGroup;
        }

        if (str_starts_with($key, 'legacy-')) {
            $legacyId = (int) substr($key, 7);

            if ($legacyId > 0) {
                $asset = BrandAsset::query()
                    ->where('brand_id', $brand->id)
                    ->where('metadata->source', $section)
                    ->where('id', $legacyId)
                    ->first();

                if ($asset) {
                    $groupId = data_get($asset->metadata, 'carousel_group');

                    if (filled($groupId)) {
                        return BrandAsset::query()
                            ->where('brand_id', $brand->id)
                            ->where('metadata->source', $section)
                            ->where('metadata->carousel_group', $groupId)
                            ->get();
                    }

                    return collect([$asset]);
                }
            }
        }

        if (ctype_digit((string) $key)) {
            $asset = BrandAsset::query()
                ->where('brand_id', $brand->id)
                ->where('metadata->source', $section)
                ->where('id', (int) $key)
                ->first();

            if (! $asset) {
                return collect();
            }

            $groupId = data_get($asset->metadata, 'carousel_group');

            if (filled($groupId)) {
                return BrandAsset::query()
                    ->where('brand_id', $brand->id)
                    ->where('metadata->source', $section)
                    ->where('metadata->carousel_group', $groupId)
                    ->get();
            }

            return collect([$asset]);
        }

        return collect();
    }

    private function updateCarouselSlot(Brand $brand, string $key, int $slot, ?UploadedFile $file, string $section = 'content_library'): void
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return;
        }

        if ($slot < 0 || $slot > 3) {
            abort(422);
        }

        $assets = $this->carouselGroupAssets($brand, $key, $section);

        if ($assets->isEmpty()) {
            abort(404);
        }

        $storageFolder = $this->storageFolderForSection($section);
        $groupId = data_get($assets->first()->metadata, 'carousel_group');

        if (! filled($groupId)) {
            $existing = $assets->first();

            if ($slot === 0) {
                $this->replaceAssetFile($brand, $existing->id, $file, 'image', null, $section);

                return;
            }

            $groupId = (string) Str::uuid();
            $meta = $existing->metadata ?? [];
            $meta['source'] = $section;
            $meta['library_type'] = $meta['library_type'] ?? 'carousel';
            $meta['carousel'] = true;
            $meta['carousel_group'] = $groupId;
            $meta['slot'] = 0;
            $existing->update(['metadata' => $meta]);
            $assets = collect([$existing]);
        }

        $slotAsset = $assets->first(fn (BrandAsset $asset) => (int) data_get($asset->metadata, 'slot', -1) === $slot);

        if ($slotAsset) {
            $this->replaceAssetFile($brand, $slotAsset->id, $file, 'image', null, $section);

            return;
        }

        $path = $file->store("brands/{$brand->id}/{$storageFolder}/carousels/{$groupId}", 'local');

        BrandAsset::query()->create([
            'brand_id' => $brand->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => 'local',
            'file_type' => 'image',
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'indexed',
            'indexed_at' => now(),
            'metadata' => $this->libraryMetadata($section, [
                'library_type' => 'carousel',
                'carousel' => true,
                'carousel_group' => $groupId,
                'slot' => $slot,
            ]),
        ]);
    }

    private function deleteAssetFile(BrandAsset $asset): void
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';
        if ($asset->file_path && Storage::disk($disk)->exists($asset->file_path)) {
            Storage::disk($disk)->delete($asset->file_path);
        }

        $asset->delete();
    }

    private function updateCaptionPost(Brand $brand, string $groupKey, string $caption, ?UploadedFile $image, string $section = 'content_library'): void
    {
        $assets = $this->manualAssetsForKey($brand, 'caption', $groupKey, $section);
        $storageFolder = $this->storageFolderForSection($section);
        $captionAsset = $assets->first(fn (BrandAsset $a) => data_get($a->metadata, 'role') === 'caption'
            || filled(data_get($a->metadata, 'caption_text')));
        $imageAsset = $assets->first(fn (BrandAsset $a) => data_get($a->metadata, 'role') === 'image');

        if ($image instanceof UploadedFile && $image->isValid()) {
            if ($imageAsset) {
                $this->replaceAssetFile($brand, $imageAsset->id, $image, 'image', $groupKey, $section);
            } else {
                $path = $image->store("brands/{$brand->id}/{$storageFolder}/caption-posts/{$groupKey}", 'local');
                $imageAsset = BrandAsset::query()->create([
                    'brand_id' => $brand->id,
                    'file_name' => $image->getClientOriginalName(),
                    'file_path' => $path,
                    'disk' => 'local',
                    'file_type' => 'image',
                    'mime_type' => $image->getMimeType(),
                    'file_size' => $image->getSize(),
                    'status' => 'indexed',
                    'indexed_at' => now(),
                    'metadata' => $this->libraryMetadata($section, [
                        'library_type' => 'caption',
                        'content_group' => $groupKey,
                        'role' => 'image',
                    ]),
                ]);
            }
        }

        if ($caption === '' && ! $captionAsset && ! $imageAsset) {
            return;
        }

        if ($captionAsset) {
            Storage::disk('local')->put($captionAsset->file_path, $caption);
            $meta = $captionAsset->metadata ?? [];
            $meta['caption_text'] = $caption;
            $meta['paired_image_asset_id'] = $imageAsset?->id;
            $captionAsset->update([
                'file_name' => Str::limit($caption ?: 'Caption post', 48),
                'file_size' => strlen($caption),
                'metadata' => $meta,
            ]);

            return;
        }

        if ($caption !== '') {
            $path = "brands/{$brand->id}/{$storageFolder}/captions/{$groupKey}.txt";
            Storage::disk('local')->put($path, $caption);

            BrandAsset::query()->create([
                'brand_id' => $brand->id,
                'file_name' => Str::limit($caption, 48),
                'file_path' => $path,
                'disk' => 'local',
                'file_type' => 'guidelines',
                'mime_type' => 'text/plain',
                'file_size' => strlen($caption),
                'status' => 'indexed',
                'indexed_at' => now(),
                'metadata' => $this->libraryMetadata($section, [
                    'library_type' => 'caption',
                    'content_group' => $groupKey,
                    'role' => 'caption',
                    'caption_text' => $caption,
                    'paired_image_asset_id' => $imageAsset?->id,
                ]),
            ]);
        }
    }

    private function replaceAssetFile(Brand $brand, int $assetId, ?UploadedFile $file, string $libraryType, ?string $groupKey = null, string $section = 'content_library'): void
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return;
        }

        $asset = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('metadata->source', $section)
            ->where('id', $assetId)
            ->firstOrFail();

        $disk = $asset->disk === 'public' ? 'public' : 'local';
        if ($asset->file_path && Storage::disk($disk)->exists($asset->file_path)) {
            Storage::disk($disk)->delete($asset->file_path);
        }

        $storageFolder = $this->storageFolderForSection($section);
        $folder = match (true) {
            $libraryType === 'reel' => 'reels',
            filled($groupKey) => "caption-posts/{$groupKey}",
            default => 'images',
        };
        $path = $file->store("brands/{$brand->id}/{$storageFolder}/{$folder}", 'local');

        $asset->update([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $libraryType === 'reel' ? 'video' : 'image',
            'indexed_at' => now(),
        ]);
    }

    /** @param list<UploadedFile> $files */
    private function replaceCarouselGroup(Brand $brand, string $groupKey, array $files, string $section = 'content_library'): void
    {
        $validFiles = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile && $f->isValid()));

        if ($validFiles === []) {
            return;
        }

        $this->deleteManualItem($brand, 'carousel', $groupKey, $section);

        $storageFolder = $this->storageFolderForSection($section);

        foreach ($validFiles as $index => $file) {
            $path = $file->store("brands/{$brand->id}/{$storageFolder}/carousels/{$groupKey}", 'local');

            BrandAsset::query()->create([
                'brand_id' => $brand->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'disk' => 'local',
                'file_type' => 'image',
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'indexed',
                'indexed_at' => now(),
                'metadata' => $this->libraryMetadata($section, [
                    'library_type' => 'carousel',
                    'carousel' => true,
                    'carousel_group' => $groupKey,
                    'slot' => min($index, 3),
                ]),
            ]);
        }
    }

    /** @param list<array<string, mixed>> $items */
    private function finalizeVisualCategory(array $items, Brand $brand, string $categoryKey, string $mode): array
    {
        if ($categoryKey === 'reel') {
            return $this->finalizeReelItems($items, $brand, $mode);
        }

        if (! in_array($categoryKey, ['image', 'carousel'], true)) {
            return $this->renumberItems($items);
        }

        $labelPrefix = $categoryKey === 'carousel' ? 'Slide' : 'Image';
        $templates = collect($items)->take(5);

        if ($templates->isEmpty()) {
            if ($mode === 'manual') {
                return [];
            }

            $templates = collect(range(1, 5))->map(fn (int $n) => [
                'title' => $this->defaultVisualTitle($categoryKey, $n),
                'source' => 'knowledge_base',
            ]);
        }

        while ($templates->count() < 5) {
            $n = $templates->count() + 1;
            $templates->push([
                'title' => $this->defaultVisualTitle($categoryKey, $n),
                'source' => $templates->last()['source'] ?? 'knowledge_base',
            ]);
        }

        return $templates->take(5)->values()->map(function (array $template, int $index) use ($brand, $labelPrefix, $categoryKey) {
            $item = [
                'number' => $index + 1,
                'title' => $template['title'] ?? $this->defaultVisualTitle($labelPrefix === 'Slide' ? 'carousel' : 'image', $index + 1),
                'text' => '',
                'tags' => [],
                'source' => $template['source'] ?? 'knowledge_base',
            ];

            if ($categoryKey === 'image') {
                $item['image_url'] = $this->buildSinglePreviewImage($brand, $index);

                return $item;
            }

            $item['preview_images'] = $this->buildPreviewImages($brand, $labelPrefix, $index);

            return $item;
        })->all();
    }

    private function buildSinglePreviewImage(Brand $brand, int $offset = 0): ?string
    {
        $assets = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('disk', '!=', 'url')
            ->where(function ($query) {
                $query->where('file_type', 'like', 'image%')
                    ->orWhere('mime_type', 'like', 'image/%');
            })
            ->latest()
            ->get();

        if ($assets->isEmpty()) {
            return null;
        }

        $asset = $assets->get($offset % $assets->count());

        return $asset instanceof BrandAsset
            ? route('app.brand.assets.show', $asset)
            : null;
    }

    private function defaultVisualTitle(string $categoryKey, int $number): string
    {
        return match ($categoryKey) {
            'carousel' => match ($number) {
                1 => 'Carousel post',
                2 => 'Carousel: educational slides',
                3 => 'Carousel: step-by-step guide',
                4 => 'Carousel: myths vs facts',
                default => 'Carousel: feature breakdown',
            },
            default => match ($number) {
                1 => 'Image: hero brand visual',
                2 => 'Image: product showcase',
                3 => 'Image: behind the scenes',
                4 => 'Image: customer story',
                default => 'Image: seasonal campaign',
            },
        };
    }

    /** @return list<array{url: ?string, label: string}> */
    private function buildPreviewImages(Brand $brand, string $labelPrefix, int $offset = 0): array
    {
        $assets = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('disk', '!=', 'url')
            ->where(function ($query) {
                $query->where('file_type', 'like', 'image%')
                    ->orWhere('mime_type', 'like', 'image/%');
            })
            ->latest()
            ->get();

        $previews = [];

        for ($slot = 0; $slot < 4; $slot++) {
            $asset = $assets->get($assets->count() > 0 ? ($offset * 4 + $slot) % $assets->count() : null);

            if ($asset instanceof BrandAsset) {
                $previews[] = [
                    'url' => route('app.brand.assets.show', $asset),
                    'label' => $asset->file_name,
                ];

                continue;
            }

            $previews[] = [
                'url' => null,
                'label' => $labelPrefix.' '.($slot + 1),
            ];
        }

        return $previews;
    }

    /** @param list<array<string, mixed>> $items */
    private function finalizeReelItems(array $items, Brand $brand, string $mode): array
    {
        $templates = collect($items)->take(5);

        if ($templates->isEmpty()) {
            if ($mode === 'manual') {
                return [];
            }

            $templates = collect(range(1, 5))->map(fn () => [
                'source' => 'knowledge_base',
            ]);
        }

        while ($templates->count() < 5) {
            $templates->push([
                'source' => $templates->last()['source'] ?? 'knowledge_base',
            ]);
        }

        $videos = BrandAsset::query()
            ->where('brand_id', $brand->id)
            ->where('disk', '!=', 'url')
            ->where(function ($query) {
                $query->where('file_type', 'like', 'video%')
                    ->orWhere('mime_type', 'like', 'video/%');
            })
            ->latest()
            ->get();

        return $templates->take(5)->values()->map(function (array $template, int $index) use ($videos) {
            $video = $videos->count() > 0 ? $videos->get($index % $videos->count()) : null;

            return [
                'number' => $index + 1,
                'title' => '',
                'text' => '',
                'tags' => [],
                'source' => $template['source'] ?? 'knowledge_base',
                'reel_preview' => true,
                'video_url' => $video instanceof BrandAsset
                    ? route('app.brand.assets.show', $video)
                    : null,
            ];
        })->all();
    }

    /** @param list<array<string, mixed>> $items */
    private function renumberItems(array $items): array
    {
        return collect($items)->values()->map(function (array $item, int $index) {
            $item['number'] = $index + 1;

            return $item;
        })->all();
    }

    private function storageFolderForSection(string $section): string
    {
        return $section === 'post_planning' ? 'post-planning' : 'library';
    }

    /** @param array<string, mixed> $extra */
    private function libraryMetadata(string $section, array $extra = []): array
    {
        return array_merge(['source' => $section], $extra);
    }
}
