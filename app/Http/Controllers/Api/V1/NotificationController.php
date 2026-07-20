<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::query()->where('user_id', $request->user()->id);

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $this->applySorting($query, $request, ['created_at', 'read_at'], 'created_at');

        $notifications = $query->paginate($this->perPage($request));

        return $this->paginated($notifications, NotificationResource::class);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return $this->success(new NotificationResource($notification), 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(message: 'All notifications marked as read.');
    }
}
