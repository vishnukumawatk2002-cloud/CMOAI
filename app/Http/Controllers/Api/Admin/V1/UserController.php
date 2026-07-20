<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('users.view')) {
            return $this->error('Forbidden.', 403);
        }

        $query = User::query()->withCount('brands');

        $this->applySearch($query, $request->search, ['email', 'first_name', 'last_name']);
        $this->applySorting($query, $request, ['first_name', 'email', 'created_at'], 'created_at');

        $users = $query->paginate($this->perPage($request));

        return $this->paginated($users, UserResource::class);
    }

    public function show(User $user): JsonResponse
    {
        if (! auth()->user()->hasPermission('users.view')) {
            return $this->error('Forbidden.', 403);
        }

        $user->loadCount(['brands', 'subscriptions']);

        return $this->success(new UserResource($user));
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return $this->success(new UserResource($user->fresh()), 'User updated successfully.');
    }

    public function destroy(User $user): JsonResponse
    {
        if (! auth()->user()->hasPermission('users.delete')) {
            return $this->error('Forbidden.', 403);
        }

        $user->delete();

        return $this->success(message: 'User deleted successfully.');
    }
}
