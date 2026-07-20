<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentHashtagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['hashtag' => $this->hashtag];
    }
}
