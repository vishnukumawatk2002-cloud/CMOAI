<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\Content\GenerateContentDTO;
use App\Application\Services\Content\ContentGenerationService;
use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Content\GenerateContentRequest;
use App\Http\Requests\Api\Content\UpdateContentRequest;
use App\Http\Resources\AiGenerationRequestResource;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends ApiController
{
    public function __construct(
        private readonly ContentGenerationService $generator,
        private readonly ContentRepositoryInterface $content,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');

        $items = $this->content->forBrand($brand->id, [
            'status' => $request->status,
            'platform' => $request->platform,
            'folder_id' => $request->folder_id,
            'search' => $request->search,
            'per_page' => $this->perPage($request),
        ]);

        return $this->paginated($items, ContentItemResource::class);
    }

    public function show(ContentItem $contentItem): JsonResponse
    {
        $this->authorizeContent($contentItem);

        return $this->success(new ContentItemResource($contentItem->load('hashtags')));
    }

    public function generate(GenerateContentRequest $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');

        $generation = $this->generator->dispatch($request->user(), $brand, new GenerateContentDTO(
            contentType: $request->content_type,
            platforms: $request->platforms,
            prompt: $request->prompt,
        ));

        $message = config('cmo.queue_content_generation', true)
            ? 'Content generation queued successfully.'
            : 'Content generated successfully.';

        return $this->created(new AiGenerationRequestResource($generation), $message);
    }

    public function update(UpdateContentRequest $request, ContentItem $contentItem): JsonResponse
    {
        $this->authorizeContent($contentItem);

        $updated = $this->content->update($contentItem, $request->validated());

        return $this->success(new ContentItemResource($updated), 'Content updated successfully.');
    }

    public function destroy(ContentItem $contentItem): JsonResponse
    {
        $this->authorizeContent($contentItem);

        $this->content->delete($contentItem);

        return $this->success(message: 'Content deleted successfully.');
    }

    private function authorizeContent(ContentItem $content): void
    {
        $content->loadMissing('brand');

        if ($content->brand->user_id !== auth()->id()) {
            abort(403, 'You do not have access to this content.');
        }
    }
}
