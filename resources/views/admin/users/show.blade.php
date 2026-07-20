@extends('layouts.admin')

@section('title', $user->full_name)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users.index') }}" class="text-decoration-none small">&larr; Back to users</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-semibold">User Details</h2>
                @if (auth('admin')->user()->hasPermission('users.edit'))
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">Edit</a>
                @endif
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted">Name</dt>
                    <dd class="col-sm-9">{{ $user->full_name }}</dd>

                    <dt class="col-sm-3 text-muted">Email</dt>
                    <dd class="col-sm-9">{{ $user->email }}</dd>

                    <dt class="col-sm-3 text-muted">Verified</dt>
                    <dd class="col-sm-9">
                        @if ($user->email_verified_at)
                            <span class="badge bg-success">Verified</span>
                            <span class="text-muted small">{{ $user->email_verified_at->format('M d, Y') }}</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3 text-muted">Brands</dt>
                    <dd class="col-sm-9">{{ $user->brands_count }}</dd>

                    <dt class="col-sm-3 text-muted">Subscriptions</dt>
                    <dd class="col-sm-9">{{ $user->subscriptions_count }}</dd>

                    <dt class="col-sm-3 text-muted">Registered</dt>
                    <dd class="col-sm-9">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if (auth('admin')->user()->hasPermission('users.delete'))
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm border-danger">
                <div class="card-body">
                    <h3 class="h6 text-danger fw-semibold">Danger Zone</h3>
                    <p class="small text-muted">Permanently delete this user and all associated data.</p>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user permanently?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0 fw-semibold">Brands ({{ $user->brands_count }})</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Brand</th>
                    <th>Plan</th>
                    <th>Industry</th>
                    <th class="text-center">Accounts</th>
                    <th class="text-center">Content</th>
                    <th>Status</th>
                    <th>Created</th>
                    @if (auth('admin')->user()->hasPermission('brands.delete'))
                        <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($user->brands as $brand)
                    <tr>
                        <td class="fw-medium">
                            {{ $brand->name }}
                            <div class="text-muted small">{{ $brand->slug }}</div>
                        </td>
                        <td>
                            @if ($brand->plan)
                                <span class="badge bg-primary-subtle text-primary">{{ $brand->plan->name }}</span>
                            @else
                                <span class="text-muted">No plan</span>
                            @endif
                        </td>
                        <td>{{ $brand->industry ?: '—' }}</td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $brand->social_accounts_count }}</span></td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $brand->content_items_count }}</span></td>
                        <td>
                            @if ($brand->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $brand->created_at->format('M d, Y') }}</td>
                        @if (auth('admin')->user()->hasPermission('brands.delete'))
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="d-inline" onsubmit="return confirm('Delete brand &quot;{{ $brand->name }}&quot; permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth('admin')->user()->hasPermission('brands.delete') ? 8 : 7 }}" class="text-center text-muted py-4">No brands found for this user.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
