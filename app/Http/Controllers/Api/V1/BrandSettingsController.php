<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Brand\UpdateBrandSettingsRequest;
use App\Http\Resources\BrandResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandSettingsController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');
        $brand->load(['voiceSettings', 'knowledgeBase']);

        return $this->success(new BrandResource($brand));
    }

    public function update(UpdateBrandSettingsRequest $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');

        if ($brand->voiceSettings) {
            $brand->voiceSettings->update($request->validated());
        }

        return $this->success(new BrandResource($brand->fresh(['voiceSettings', 'knowledgeBase'])), 'Brand settings updated.');
    }
}
