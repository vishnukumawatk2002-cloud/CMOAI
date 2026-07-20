<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiGenerationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content_type' => $this->content_type,
            'platforms' => $this->platforms,
            'prompt' => $this->prompt,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'content_items' => ContentItemResource::collection($this->whenLoaded('contentItems')),
        ];
    }
}
