<?php

namespace App\Http\Controllers\Web\Content;

use App\Application\DTOs\Content\GenerateContentDTO;
use App\Application\Services\Content\ContentGenerationService;
use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\UpdateContentRequest;
use App\Http\Requests\Web\Content\GenerateContentRequest;
use App\Models\ContentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(
        private readonly ContentGenerationService $generator,
        private readonly ContentRepositoryInterface $content,
    ) {
    }

    public function generateForm(): View
    {
        return view('app.content.generate');
    }

    public function generate(GenerateContentRequest $request): RedirectResponse
    {
        $brand = $request->attributes->get('current_brand');

        $this->generator->dispatch($request->user(), $brand, new GenerateContentDTO(
            contentType: $request->content_type,
            platforms: $request->platforms,
            prompt: $request->prompt,
        ));

        $message = config('cmo.queue_content_generation', true)
            ? 'Content generation started. Check your library shortly.'
            : 'Content generated successfully.';

        return redirect()->route('app.brand.content-library')->with('success', $message);
    }

    public function library(Request $request): View
    {
        $brand = $request->attributes->get('current_brand');

        $items = $this->content->forBrand($brand->id, [
            'status' => $request->status,
            'platform' => $request->platform,
            'folder_id' => $request->folder_id,
            'search' => $request->search,
        ]);

        return view('app.content.library', [
            'items' => $items,
            'statusCounts' => $this->content->countByStatus($brand->id),
        ]);
    }

    public function edit(Request $request, ContentItem $contentItem): View
    {
        $this->authorizeContent($request, $contentItem);

        return view('app.content.edit', compact('contentItem'));
    }

    public function update(UpdateContentRequest $request, ContentItem $contentItem): RedirectResponse
    {
        $this->authorizeContent($request, $contentItem);

        $this->content->update($contentItem, $request->validated());

        return redirect()->route('app.content.library')->with('success', 'Content updated successfully.');
    }

    public function destroy(Request $request, ContentItem $contentItem): RedirectResponse
    {
        $this->authorizeContent($request, $contentItem);

        $this->content->delete($contentItem);

        return redirect()->route('app.content.library')->with('success', 'Content deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:approve,delete'],
        ]);

        $brand = $request->attributes->get('current_brand');
        $ids = ContentItem::query()
            ->where('brand_id', $brand->id)
            ->whereIn('id', $request->ids)
            ->pluck('id')
            ->all();

        if ($request->action === 'approve') {
            $this->content->bulkUpdateStatus($ids, 'approved');

            return back()->with('success', count($ids).' item(s) approved.');
        }

        ContentItem::query()->whereIn('id', $ids)->delete();

        return back()->with('success', count($ids).' item(s) deleted.');
    }

    private function authorizeContent(Request $request, ContentItem $contentItem): void
    {
        $brand = $request->attributes->get('current_brand');

        if ($contentItem->brand_id !== $brand->id) {
            abort(403);
        }
    }
}
