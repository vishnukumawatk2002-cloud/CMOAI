<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\RoleStoreRequest;
use App\Http\Requests\Admin\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('roles.view')) {
            return $this->error('Forbidden.', 403);
        }

        $query = Role::query()->withCount(['permissions', 'admins']);

        $this->applySearch($query, $request->search, ['name', 'slug']);
        $this->applySorting($query, $request, ['name', 'created_at'], 'name');

        $roles = $query->paginate($this->perPage($request));

        return $this->paginated($roles, RoleResource::class);
    }

    public function store(RoleStoreRequest $request): JsonResponse
    {
        $role = Role::query()->create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        return $this->created(new RoleResource($role->load('permissions')), 'Role created successfully.');
    }

    public function show(Role $role): JsonResponse
    {
        if (! auth()->user()->hasPermission('roles.view')) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success(new RoleResource($role->load('permissions')));
    }

    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        return $this->success(new RoleResource($role->fresh('permissions')), 'Role updated successfully.');
    }

    public function destroy(Role $role): JsonResponse
    {
        if (! auth()->user()->hasPermission('roles.delete')) {
            return $this->error('Forbidden.', 403);
        }

        if ($role->slug === 'super_admin') {
            return $this->error('The Super Admin role cannot be deleted.', 422);
        }

        if ($role->admins()->exists()) {
            return $this->error('Cannot delete a role assigned to admins.', 422);
        }

        $role->delete();

        return $this->success(message: 'Role deleted successfully.');
    }

    public function permissions(): JsonResponse
    {
        if (! auth()->user()->hasPermission('roles.view')) {
            return $this->error('Forbidden.', 403);
        }

        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get();

        return $this->success($permissions->groupBy('group'));
    }
}
