<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\PermissionStoreRequest;
use App\Http\Requests\Admin\PermissionUpdateRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('permissions.view')) {
            return $this->error('Forbidden.', 403);
        }

        $query = Permission::query()->withCount('roles');

        $this->applySearch($query, $request->search, ['name', 'slug', 'group']);
        $this->applySorting($query, $request, ['name', 'group', 'created_at'], 'group');

        $permissions = $query->paginate($this->perPage($request));

        return $this->paginated($permissions, PermissionResource::class);
    }

    public function store(PermissionStoreRequest $request): JsonResponse
    {
        $permission = Permission::query()->create($request->validated());

        return $this->created(new PermissionResource($permission), 'Permission created successfully.');
    }

    public function show(Permission $permission): JsonResponse
    {
        if (! auth()->user()->hasPermission('permissions.view')) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success(new PermissionResource($permission));
    }

    public function update(PermissionUpdateRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->validated());

        return $this->success(new PermissionResource($permission->fresh()), 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        if (! auth()->user()->hasPermission('permissions.delete')) {
            return $this->error('Forbidden.', 403);
        }

        if ($permission->roles()->exists()) {
            return $this->error('Cannot delete a permission assigned to roles.', 422);
        }

        $permission->delete();

        return $this->success(message: 'Permission deleted successfully.');
    }
}
