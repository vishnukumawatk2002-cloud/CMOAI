<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandVoiceSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tone_style' => $this->tone_style,
            'company_description' => $this->company_description,
            'products_services' => $this->products_services,
            'target_audience' => $this->target_audience,
            'keywords' => $this->keywords,
            'avoid_words' => $this->avoid_words,
        ];
    }
}
