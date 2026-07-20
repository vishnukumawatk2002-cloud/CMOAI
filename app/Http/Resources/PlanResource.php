<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'max_brands' => null,
            'max_social_accounts' => $this->max_social_accounts,
            'max_posts_per_month' => $this->max_posts_per_month,
            'bulk_scheduling' => $this->bulk_scheduling,
            'ai_insights' => $this->ai_insights,
            'white_label_reports' => $this->white_label_reports,
            'api_access' => $this->api_access,
            'feature_list' => $this->editableFeatures(),
            'features' => $this->enabledFeatureNames(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
