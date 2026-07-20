<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'website' => $this->website,
            'industry' => $this->industry,
            'country' => $this->country,
            'language' => $this->language,
            'tone' => $this->tone,
            'founded_year' => $this->founded_year,
            'short_description' => $this->short_description,
            'logo_path' => $this->logo_path,
            'setup_step' => $this->setup_step,
            'setup_completed_at' => $this->setup_completed_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'voice_settings' => new BrandVoiceSettingResource($this->whenLoaded('voiceSettings')),
            'knowledge_base' => new BrandKnowledgeBaseResource($this->whenLoaded('knowledgeBase')),
            'social_accounts_count' => $this->whenCounted('socialAccounts'),
            'content_items_count' => $this->whenCounted('contentItems'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
