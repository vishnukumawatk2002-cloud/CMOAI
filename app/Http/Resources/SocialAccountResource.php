<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'platform' => $this->platform,
            'account_name' => $this->account_name,
            'account_handle' => $this->account_handle,
            'account_type' => $this->account_type,
            'follower_count' => $this->follower_count,
            'profile_image_url' => $this->profile_image_url,
            'status' => $this->status,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
