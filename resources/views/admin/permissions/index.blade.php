@extends('layouts.admin')

@section('title', 'Permissions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage granular access permissions</p>
    @if (auth('admin')->user()->hasPermission('permissions.create'))
        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary btn-sm">Add Permission</a>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Group</th>
                    <th>Roles</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permissions as $permission)
                    <tr>
                        <td class="fw-medium">{{ $permission->name }}</td>
                        <td><code>{{ $permission->slug }}</code></td>
                        <td><span class="badge bg-light text-dark">{{ $permission->group }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $permission->roles_count }}</span></td>
                        <td class="text-end">
                            @if (auth('admin')->user()->hasPermission('permissions.edit'))
                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endif
                            @if (auth('admin')->user()->hasPermission('permissions.delete'))
                                <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="d-inline" onsubmit="return confirm('Delete this permission?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No permissions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($permissions->hasPages())
        <div class="card-footer bg-white">{{ $permissions->links() }}</div>
    @endif
</div>
@endsection
