<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Schedule\StoreScheduledPostRequest;
use App\Http\Resources\ScheduledPostResource;
use App\Models\ScheduledPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');

        $query = ScheduledPost::query()
            ->whereHas('contentItem', fn ($q) => $q->where('brand_id', $brand->id))
            ->with('contentItem');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $this->applySorting($query, $request, ['scheduled_at', 'created_at'], 'scheduled_at');

        $posts = $query->paginate($this->perPage($request));

        return $this->paginated($posts, ScheduledPostResource::class);
    }

    public function store(StoreScheduledPostRequest $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');

        $contentItem = $brand->contentItems()->find($request->content_item_id);
        $socialAccount = $brand->socialAccounts()->find($request->social_account_id);

        if (! $contentItem || ! $socialAccount) {
            return $this->error('Invalid content or social account for this brand.', 422);
        }

        $post = ScheduledPost::query()->create([
            'content_item_id' => $contentItem->id,
            'social_account_id' => $socialAccount->id,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending',
        ]);

        return $this->created(new ScheduledPostResource($post->load('contentItem')), 'Post scheduled successfully.');
    }

    public function destroy(ScheduledPost $scheduledPost): JsonResponse
    {
        if ($scheduledPost->contentItem->brand->user_id !== auth()->id()) {
            abort(403);
        }

        $scheduledPost->delete();

        return $this->success(message: 'Scheduled post deleted successfully.');
    }
}
