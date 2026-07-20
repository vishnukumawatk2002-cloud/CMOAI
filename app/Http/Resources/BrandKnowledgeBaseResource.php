<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandKnowledgeBaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'detected_tone' => $this->detected_tone,
            'detected_audience' => $this->detected_audience,
            'detected_services' => $this->detected_services,
            'top_keywords' => $this->top_keywords,
            'training_status' => $this->training_status,
            'last_trained_at' => $this->last_trained_at?->toIso8601String(),
        ];
    }
}
