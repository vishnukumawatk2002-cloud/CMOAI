<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends ApiController
{
    public function index(): JsonResponse
    {
        if (! auth()->user()->hasPermission('settings.view')) {
            return $this->error('Forbidden.', 403);
        }

        $settings = Setting::query()->orderBy('group')->orderBy('label')->get();

        return $this->success(
            SettingResource::collection($settings)->resolve()
        );
    }

    public function update(SettingUpdateRequest $request): JsonResponse
    {
        foreach ($request->input('settings', []) as $key => $value) {
            Setting::set($key, $value);
        }

        return $this->success(message: 'Settings saved successfully.');
    }
}
