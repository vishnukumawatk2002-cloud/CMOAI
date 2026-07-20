<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\Auth\AdminLoginRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $admin = Admin::query()->where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return $this->error('Invalid admin credentials.', 401);
        }

        if (! $admin->is_active) {
            return $this->error('Admin account is deactivated.', 403);
        }

        $token = $admin->createToken($request->input('device_name', 'admin-api'))->plainTextToken;

        return $this->success([
            'admin' => new AdminResource($admin->load('roles')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(message: 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new AdminResource($request->user()->load('roles')));
    }
}
