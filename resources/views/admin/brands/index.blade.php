@extends('layouts.admin')

@section('title', 'All Brands')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage all brands across the platform</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.brands.index') }}" class="row g-2">
            <div class="col-md-6">
                <input type="search" name="search" class="form-control" placeholder="Search by brand, industry or owner..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
                @if (request('search') || request('status'))
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Brand</th>
                    <th>Owner</th>
                    <th>Plan</th>
                    <th>Industry</th>
                    <th class="text-center">Accounts</th>
                    <th class="text-center">Content</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($brands as $brand)
                    <tr>
                        <td class="fw-medium">
                            {{ $brand->name }}
                            <div class="text-muted small">{{ $brand->slug }}</div>
                        </td>
                        <td>
                            @if ($brand->user)
                                {{ $brand->user->full_name }}
                                <div class="text-muted small">{{ $brand->user->email }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No brands found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($brands->hasPages())
        <div class="card-footer bg-white">
            {{ $brands->links() }}
        </div>
    @endif
</div>
@endsection
