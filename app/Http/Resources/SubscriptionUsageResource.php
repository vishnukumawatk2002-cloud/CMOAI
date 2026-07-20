<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'posts_used' => $this->posts_used,
            'brands_count' => $this->brands_count,
            'social_accounts_count' => $this->social_accounts_count,
        ];
    }
}
