<?php

namespace App\Http\Controllers\Web\Brand;

use App\Application\Services\Brand\BrandService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandSettingsController extends Controller
{
    public function __construct(private readonly BrandService $brands)
    {
    }

    public function edit(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');

        return view('app.brand.settings', [
            'brand' => $brand->load(['voiceSettings', 'knowledgeBase']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:50'],
            'tone' => ['nullable', 'string', 'max:100'],
        ]);

        $brand->update($validated);

        return back()->with('success', 'Brand settings saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $this->brands->deleteBrand($request->user(), $brand);

        if ($this->brands->currentBrand($request->user())) {
            return redirect()
                ->route('app.dashboard')
                ->with('success', 'Brand deleted successfully.');
        }

        return redirect()
            ->route('onboarding.brand.create')
            ->with('success', 'Brand deleted. Create a new brand to continue.');
    }
}
