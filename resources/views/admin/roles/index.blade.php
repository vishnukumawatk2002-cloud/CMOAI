@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage admin roles and access levels</p>
    @if (auth('admin')->user()->hasPermission('roles.create'))
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">Add Role</a>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Permissions</th>
                    <th>Admins</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td class="fw-medium">{{ $role->name }}</td>
                        <td><code>{{ $role->slug }}</code></td>
                        <td><span class="badge bg-light text-dark">{{ $role->permissions_count }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $role->admins_count }}</span></td>
                        <td>
                            @if ($role->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if (auth('admin')->user()->hasPermission('roles.edit'))
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endif
                            @if (auth('admin')->user()->hasPermission('roles.delete') && $role->slug !== 'super_admin')
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($roles->hasPages())
        <div class="card-footer bg-white">{{ $roles->links() }}</div>
    @endif
</div>
@endsection
