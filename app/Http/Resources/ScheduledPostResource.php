<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content_item_id' => $this->content_item_id,
            'social_account_id' => $this->social_account_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'failure_reason' => $this->failure_reason,
            'retry_count' => $this->retry_count,
            'content_item' => new ContentItemResource($this->whenLoaded('contentItem')),
        ];
    }
}
