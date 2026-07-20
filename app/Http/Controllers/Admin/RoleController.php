<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleStoreRequest;
use App\Http\Requests\Admin\RoleUpdateRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'admins'])
            ->latest()
            ->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $role = Role::query()->create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');
        $role->load('permissions');

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (! auth('admin')->user()->hasPermission('roles.delete')) {
            abort(403);
        }

        if ($role->slug === 'super_admin') {
            return back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->admins()->exists()) {
            return back()->with('error', 'Cannot delete a role assigned to admins.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted successfully.');
    }
}
