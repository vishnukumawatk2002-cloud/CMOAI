<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionStoreRequest;
use App\Http\Requests\Admin\PermissionUpdateRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('group')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('admin.permissions.create');
    }

    public function store(PermissionStoreRequest $request): RedirectResponse
    {
        Permission::query()->create($request->validated());

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission created successfully.');
    }

    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(PermissionUpdateRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update($request->validated());

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if (! auth('admin')->user()->hasPermission('permissions.delete')) {
            abort(403);
        }

        if ($permission->roles()->exists()) {
            return back()->with('error', 'Cannot delete a permission assigned to roles.');
        }

        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission deleted successfully.');
    }
}
