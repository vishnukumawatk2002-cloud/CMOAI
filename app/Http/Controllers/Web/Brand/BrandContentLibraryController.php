<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandContentLibraryService;
use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandProfileService;
use App\Application\Services\Brand\PlanAccessService;
use App\Http\Controllers\Controller;
use App\Models\BrandAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandContentLibraryController extends Controller
{
    protected string $librarySection = 'content_library';

    protected string $viewName = 'app.brand.content-library';

    protected string $routeName = 'app.brand.content-library';

    protected string $storageFolder = 'library';

    protected string $defaultTab = 'ai';

    public function __construct(
        private readonly BrandKnowledgeBaseService $knowledgeBase,
        private readonly BrandProfileService $profiles,
        private readonly BrandContentLibraryService $library,
        private readonly PlanAccessService $planAccess,
    ) {
    }

    public function index(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');
        $requestedTab = $request->query('tab', $this->defaultTab) === 'manual' ? 'manual' : 'ai';
        $canAccessAi = $this->planAccess->canAccessGeneratedAiLibrary($request->user(), $brand);
        $canAccessReels = $this->planAccess->canAccessReels($request->user(), $brand);

        if ($this->routeName === 'app.brand.content-library' && $requestedTab === 'ai' && ! $canAccessAi) {
            $tab = 'ai';
            $aiLocked = true;
        } else {
            $tab = $requestedTab;
            $aiLocked = false;
        }

        if ($tab === 'ai' && ! $aiLocked) {
            $this->knowledgeBase->ensureTrained($brand);
        }

        $brand->load(['voiceSettings', 'knowledgeBase', 'suggestedPrompts']);

        $profile = ($tab === 'ai' && ! $aiLocked)
            ? $this->profiles->build($brand)
            : [
                'knowledge_base' => $brand->knowledgeBase,
                'ai_analysis' => [],
            ];

        $categories = $aiLocked
            ? []
            : ($tab === 'manual'
                ? $this->library->buildManualCategories($brand, $this->librarySection)
                : $this->library->buildAiCategories($brand, $profile['ai_analysis'] ?? []));

        // Plan lock applies only to AI-generated Reels — manual uploads always keep Reels.
        if ($tab === 'ai' && ! $canAccessReels) {
            $categories = collect($categories)
                ->reject(fn (array $category) => ($category['key'] ?? '') === 'reel')
                ->values()
                ->all();
        }

        return view($this->viewName, [
            'brand' => $brand,
            'tab' => $tab,
            'routeName' => $this->routeName,
            'kbReady' => ($profile['knowledge_base']?->training_status ?? '') === 'complete',
            'aiProvider' => data_get($profile['ai_analysis'], 'provider', 'local'),
            'categories' => $categories,
            'aiFeatureLocked' => $aiLocked,
            'canAccessAiReels' => $canAccessReels,
            'canAccessReels' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $request->validate([
            'library_type' => ['required', 'in:caption,image,reel,carousel'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:5000'],
            'caption_images' => ['nullable', 'array'],
            'caption_images.*' => ['nullable', 'file', 'image', 'max:131072'],
            'images' => ['required_if:library_type,image', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'max:131072'],
            'videos' => ['required_if:library_type,reel', 'array', 'min:1'],
            'videos.*' => ['file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:131072'],
            'carousel_images' => ['required_if:library_type,carousel', 'array', 'min:1'],
            'carousel_images.*' => ['file', 'image', 'max:131072'],
        ]);

        if ($request->library_type === 'caption' && ! $this->hasCaptionPostInput($request)) {
            return back()
                ->withInput()
                ->withErrors(['captions' => 'Add at least one caption or image.']);
        }

        $saved = match ($request->library_type) {
            'caption' => $this->storeCaptionPosts(
                $brand,
                $request->input('captions', []),
                $request->file('caption_images', [])
            ),
            'image' => $this->storeFiles($brand, $request->file('images', []), 'image'),
            'reel' => $this->storeFiles($brand, $request->file('videos', []), 'reel'),
            'carousel' => $this->storeCarousel($brand, $request->file('carousel_images', [])),
            default => 0,
        };

        return redirect()
            ->route($this->routeName, ['tab' => 'manual'])
            ->with('success', $saved.' item(s) saved to library.');
    }

    public function updateManual(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([
            'manual_type' => ['required', 'in:caption,image,reel,carousel'],
            'manual_key' => ['required', 'string'],
            'caption' => ['nullable', 'string', 'max:5000'],
            'caption_image' => ['nullable', 'file', 'image', 'max:131072'],
            'replace_image' => ['nullable', 'file', 'image', 'max:131072'],
            'replace_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:131072'],
            'carousel_images' => ['nullable', 'array'],
            'carousel_images.*' => ['file', 'image', 'max:131072'],
            'carousel_slot' => ['nullable', 'integer', 'min:0', 'max:3'],
        ]);

        $type = $validated['manual_type'];

        $file = match ($type) {
            'caption' => $request->file('caption_image'),
            'image' => $request->file('replace_image'),
            'reel' => $request->file('replace_video'),
            'carousel' => $request->file('replace_image'),
            default => null,
        };

        $this->library->updateManualItem($brand, $type, $validated['manual_key'], [
            'caption' => $validated['caption'] ?? '',
            'carousel_slot' => $validated['carousel_slot'] ?? null,
            'carousel_images' => $request->file('carousel_images', []),
        ], $file, $this->librarySection);

        return redirect()
            ->route($this->routeName, ['tab' => 'manual'])
            ->with('success', 'Content updated.');
    }

    public function destroyManualCarouselSlot(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([
            'asset_id' => ['required', 'integer'],
        ]);

        $this->library->deleteCarouselSlot($brand, (int) $validated['asset_id']);

        return redirect()
            ->route($this->routeName, ['tab' => 'manual'])
            ->with('success', 'Carousel slide deleted.');
    }

    public function destroyManual(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([
            'manual_type' => ['required', 'in:caption,image,reel,carousel'],
            'manual_key' => ['required', 'string'],
        ]);

        if (! $this->library->deleteManualItem($brand, $validated['manual_type'], $validated['manual_key'], $this->librarySection)) {
            return redirect()
                ->route($this->routeName, ['tab' => 'manual'])
                ->with('error', 'Content not found or already deleted.');
        }

        return redirect()
            ->route($this->routeName, ['tab' => 'manual'])
            ->with('success', 'Content deleted.');
    }

    public function destroyAi(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([
            'content_item_id' => ['required', 'integer'],
        ]);

        if (! $this->library->deleteAiContentItem($brand, (int) $validated['content_item_id'])) {
            return back()->with('error', 'Content not found or could not be deleted.');
        }

        return back()->with('success', 'Content deleted.');
    }

    public function showAsset(Request $request, BrandAsset $asset): StreamedResponse
    {
        $brand = $request->attributes->get('current_brand');

        if ($asset->brand_id !== $brand->id) {
            abort(403);
        }

        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $asset->file_path,
            $asset->file_name,
            ['Content-Type' => $asset->mime_type ?: 'application/octet-stream']
        );
    }

    /** @param list<string|null> $captions @param list<UploadedFile|null> $images */
    private function storeCaptionPosts($brand, array $captions, array $images): int
    {
        $saved = 0;
        $total = max(count($captions), count($images));

        for ($index = 0; $index < $total; $index++) {
            $caption = trim((string) ($captions[$index] ?? ''));
            $file = $images[$index] ?? null;

            if ($caption === '' && ! ($file instanceof UploadedFile && $file->isValid())) {
                continue;
            }

            $groupId = (string) Str::uuid();
            $imageAssetId = null;

            if ($file instanceof UploadedFile && $file->isValid()) {
                $path = $file->store("brands/{$brand->id}/{$this->storageFolder}/caption-posts/{$groupId}", 'local');

                $imageAsset = BrandAsset::query()->create([
                    'brand_id' => $brand->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'disk' => 'local',
                    'file_type' => 'image',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'indexed',
                    'indexed_at' => now(),
                    'metadata' => $this->libraryMetadata([
                        'library_type' => 'caption',
                        'content_group' => $groupId,
                        'role' => 'image',
                    ]),
                ]);

                $imageAssetId = $imageAsset->id;
            }

            if ($caption !== '') {
                $path = "brands/{$brand->id}/{$this->storageFolder}/captions/{$groupId}.txt";
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
                    'metadata' => $this->libraryMetadata([
                        'library_type' => 'caption',
                        'content_group' => $groupId,
                        'role' => 'caption',
                        'caption_text' => $caption,
                        'paired_image_asset_id' => $imageAssetId,
                    ]),
                ]);
            }

            $saved++;
        }

        return $saved;
    }

    private function hasCaptionPostInput(Request $request): bool
    {
        foreach ($request->input('captions', []) as $caption) {
            if (filled(trim((string) $caption))) {
                return true;
            }
        }

        foreach ($request->file('caption_images', []) as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                return true;
            }
        }

        return false;
    }

    /** @param list<UploadedFile> $files */
    private function storeFiles($brand, array $files, string $libraryType): int
    {
        $saved = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("brands/{$brand->id}/{$this->storageFolder}/{$libraryType}s", 'local');
            $fileType = $libraryType === 'reel' ? 'video' : 'image';

            BrandAsset::query()->create([
                'brand_id' => $brand->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'disk' => 'local',
                'file_type' => $fileType,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'indexed',
                'indexed_at' => now(),
                'metadata' => $this->libraryMetadata([
                    'library_type' => $libraryType,
                ]),
            ]);

            $saved++;
        }

        return $saved;
    }

    /** @param list<UploadedFile> $files */
    private function storeCarousel($brand, array $files): int
    {
        $groupId = (string) Str::uuid();
        $saved = 0;

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("brands/{$brand->id}/{$this->storageFolder}/carousels/{$groupId}", 'local');

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
                'metadata' => $this->libraryMetadata([
                    'library_type' => 'carousel',
                    'carousel' => true,
                    'carousel_group' => $groupId,
                    'slot' => min($index, 3),
                ]),
            ]);

            $saved++;
        }

        return $saved;
    }

    /** @param array<string, mixed> $extra */
    protected function libraryMetadata(array $extra = []): array
    {
        return array_merge(['source' => $this->librarySection], $extra);
    }
}
