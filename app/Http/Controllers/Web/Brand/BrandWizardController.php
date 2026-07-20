<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandWizardPersistenceService;
use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Application\Services\Brand\BrandService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\BrandVoiceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandWizardController extends Controller
{
    public function __construct(
        private readonly BrandWizardPersistenceService $persistence,
        private readonly BrandKnowledgeBaseService $knowledgeBase,
        private readonly BrandService $brandService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $brand = $this->brandService->currentBrand($request->user());

        if (! $brand) {
            return redirect()->route('onboarding.brand.create');
        }

        $brand->load('voiceSettings');

        session(['current_brand_id' => $brand->id]);

        $step = (int) session('step', max(1, (int) $brand->setup_step));

        $uploadedAssets = $brand->assets()
            ->where('disk', '!=', 'url')
            ->whereIn('file_type', ['image', 'pdf', 'video', 'audio', 'docx', 'logo'])
            ->latest()
            ->get()
            ->map(function ($asset) {
                $kind = $this->assetPreviewKind(
                    (string) $asset->file_type,
                    (string) $asset->file_name,
                    (string) ($asset->mime_type ?? ''),
                );

                return [
                    'id' => $asset->id,
                    'name' => $asset->file_name,
                    'size' => $this->formatBytes((int) $asset->file_size),
                    'kind' => $kind,
                    'url' => route('onboarding.wizard.asset', $asset),
                ];
            })
            ->all();

        if ($uploadedAssets === []) {
            $uploadedAssets = collect(session("brand_{$brand->id}_assets", []))
                ->map(function (array $asset) {
                    $name = (string) ($asset['name'] ?? 'file');
                    $kind = $this->assetPreviewKind('', $name, '');

                    return [
                        'id' => null,
                        'name' => $name,
                        'size' => $this->formatBytes((int) ($asset['size'] ?? 0)),
                        'kind' => $kind,
                        'url' => null,
                    ];
                })->all();
        }

        $socialUrls = session("brand_{$brand->id}_social_urls", []);
        $referenceUrls = session("brand_{$brand->id}_reference_urls", []);
        $connectedAccounts = $brand->socialAccounts()
            ->where('status', 'active')
            ->get()
            ->keyBy('platform');

        return view('onboarding.wizard', compact('brand', 'step', 'uploadedAssets', 'socialUrls', 'referenceUrls', 'connectedAccounts'));
    }

    public function saveStep(Request $request, int $step): RedirectResponse
    {
        $brand = $this->brandService->currentBrand($request->user());

        if (! $brand) {
            return redirect()->route('onboarding.brand.create');
        }

        $brand->load('voiceSettings');

        return match ($step) {
            1 => $this->saveBusinessStep($request, $brand),
            2 => $this->saveAssetsStep($request, $brand),
            3 => $this->saveBrandUrlStep($request, $brand),
            4 => $this->saveReferenceUrlsStep($request, $brand),
            5 => $this->completeWizard($brand),
            default => redirect()->route('onboarding.wizard'),
        };
    }

    private function saveBusinessStep(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:500'],
            'company_description' => ['required', 'string', 'max:5000'],
            'products_services' => ['nullable', 'string', 'max:5000'],
            'target_audience' => ['nullable', 'string', 'max:5000'],
            'tone' => ['required', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:50'],
            'industry' => ['required', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'keywords' => ['nullable', 'string', 'max:1000'],
        ]);

        $toneMap = [
            'Professional & authoritative' => 'professional',
            'Casual & friendly' => 'casual',
            'Bold & energetic' => 'bold',
            'Educational & helpful' => 'educational',
        ];

        $keywords = collect(explode(',', $validated['keywords'] ?? ''))
            ->map(fn (string $word) => trim($word))
            ->filter()
            ->values()
            ->all();

        $brand->update([
            'name' => $validated['name'],
            'website' => $validated['website'],
            'industry' => $validated['industry'],
            'country' => $validated['country'] ?? $brand->country,
            'language' => $validated['language'] ?? $brand->language,
            'tone' => $validated['tone'],
            'short_description' => $validated['company_description'],
            'setup_step' => 2,
        ]);

        BrandVoiceSetting::query()->updateOrCreate(
            ['brand_id' => $brand->id],
            [
                'tone_style' => $toneMap[$validated['tone']] ?? 'professional',
                'company_description' => $validated['company_description'],
                'products_services' => $validated['products_services'],
                'target_audience' => $validated['target_audience'],
                'keywords' => $keywords ?: null,
            ]
        );

        $this->persistence->markSourcesUpdated($brand->fresh());

        return redirect()
            ->route('onboarding.wizard')
            ->with('step', 2)
            ->with('success', 'Business information saved.');
    }

    private function saveAssetsStep(Request $request, Brand $brand): RedirectResponse
    {
        try {
            $request->validate([
                'assets' => ['nullable', 'array'],
                'assets.*' => ['file', 'max:102400'],
            ], [
                'assets.*.max' => 'Each file must be 100 MB or smaller. Please compress large videos and try again.',
                'assets.*.file' => 'Please upload a valid file.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('onboarding.wizard')
                ->with('step', 2)
                ->withErrors($e->errors())
                ->withInput();
        }

        $stored = session("brand_{$brand->id}_assets", []);

        foreach ($request->file('assets', []) as $file) {
            $path = $file->store("brands/{$brand->id}/assets", 'local');
            $stored[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
            ];
        }

        session(["brand_{$brand->id}_assets" => $stored]);

        try {
            $this->persistence->syncSessionFiles($brand);
        } catch (\Throwable) {
            // Session backup remains; DB sync retried on wizard completion.
        }

        $uploadedCount = count($request->file('assets', []));
        $brand->update(['setup_step' => $uploadedCount > 0 ? max(2, (int) $brand->setup_step) : 3]);

        return redirect()
            ->route('onboarding.wizard')
            ->with('step', $uploadedCount > 0 ? 2 : 3)
            ->with('success', $uploadedCount > 0
                ? 'Brand assets uploaded successfully.'
                : 'You can upload assets later from brand settings.');
    }

    private function saveBrandUrlStep(Request $request, Brand $brand): RedirectResponse
    {
        $prefixes = [
            'fb' => 'https://www.facebook.com/',
            'ig' => 'https://www.instagram.com/',
            'li' => 'https://www.linkedin.com/',
            'x' => 'https://x.com/',
        ];

        $validated = $request->validate([
            'brand_website' => ['nullable', 'string', 'max:500'],
            'social_paths' => ['nullable', 'array'],
            'social_paths.*' => ['nullable', 'string', 'max:400'],
        ]);

        $socialUrls = [];

        foreach ($prefixes as $id => $prefix) {
            $path = trim((string) ($validated['social_paths'][$id] ?? ''));
            $path = ltrim($path, '/');

            if ($path === '') {
                continue;
            }

            // If user pasted a full URL, keep it when valid; otherwise prefix + path.
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $socialUrls[$id] = $path;
            } else {
                $socialUrls[$id] = $prefix.$path;
            }
        }

        $request->merge(['social_urls' => $socialUrls]);

        $request->validate([
            'social_urls' => ['nullable', 'array'],
            'social_urls.*' => ['nullable', 'url', 'max:500'],
        ]);

        $updates = ['setup_step' => 4];

        if (array_key_exists('brand_website', $validated)) {
            $updates['website'] = $validated['brand_website'];
        }

        $brand->update($updates);

        session(["brand_{$brand->id}_social_urls" => $socialUrls]);

        try {
            $this->persistence->syncSessionUrls($brand);
        } catch (\Throwable) {
            // Session backup remains; DB sync retried on wizard completion.
        }

        return redirect()
            ->route('onboarding.wizard')
            ->with('step', 4)
            ->with('success', 'Brand URLs saved.');
    }

    private function saveReferenceUrlsStep(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'reference_urls' => ['nullable', 'array', 'max:10'],
            'reference_urls.*' => ['nullable', 'url', 'max:500'],
        ]);

        $referenceUrls = collect($validated['reference_urls'] ?? [])
            ->filter(fn (?string $url) => filled($url))
            ->values()
            ->all();

        session(["brand_{$brand->id}_reference_urls" => $referenceUrls]);

        try {
            $this->persistence->syncSessionUrls($brand);
        } catch (\Throwable) {
            // Session backup remains; DB sync retried on wizard completion.
        }

        $brand->update(['setup_step' => 5]);

        return redirect()
            ->route('onboarding.wizard')
            ->with('step', 5)
            ->with('success', 'Reference URLs saved.');
    }

    private function completeWizard(Brand $brand): RedirectResponse
    {
        try {
            $this->persistence->persistFromSession($brand);
        } catch (\Throwable) {
            // Final attempt uses session backup; dashboard will auto-heal if needed.
        }

        $brand->update([
            'setup_step' => 5,
            'setup_completed_at' => now(),
        ]);

        $this->knowledgeBase->trainSafely($brand->fresh());

        session(['current_brand_id' => $brand->id]);

        return redirect()
            ->route('app.dashboard')
            ->with('success', 'Welcome to CMO AI! Your brand is ready — we\'re already learning from your data.');
    }

    public function showAsset(Request $request, BrandAsset $asset): StreamedResponse
    {
        $brand = $this->brandService->currentBrand($request->user());

        if (! $brand || $asset->brand_id !== $brand->id) {
            abort(403);
        }

        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $asset->file_path,
            $asset->file_name,
            [
                'Content-Type' => $asset->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$asset->file_name.'"',
            ]
        );
    }

    private function assetPreviewKind(string $fileType, string $fileName, string $mimeType): string
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
