<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'folder_id' => $this->folder_id,
            'content_type' => $this->content_type,
            'platform' => $this->platform,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'variation_number' => $this->variation_number,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'reach' => $this->reach,
            'engagement_rate' => $this->engagement_rate,
            'hashtags' => ContentHashtagResource::collection($this->whenLoaded('hashtags')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
