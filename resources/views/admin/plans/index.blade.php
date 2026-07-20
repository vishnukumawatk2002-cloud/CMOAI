@extends('layouts.admin')

@section('title', 'Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage subscription plans and pricing</p>
    @if (auth('admin')->user()->hasPermission('plans.create'))
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">Add Plan</a>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.plans.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Name or slug..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if (request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive plans-table-scroll">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    @include('admin.plans.partials.sortable-th', ['column' => 'name', 'label' => 'Name'])
                    <th>Slug</th>
                    @include('admin.plans.partials.sortable-th', ['column' => 'price_monthly', 'label' => 'Monthly'])
                    <th>Posts/mo</th>
                    <th>Features</th>
                    <th>Subscriptions</th>
                    <th>Status</th>
                    @include('admin.plans.partials.sortable-th', ['column' => 'sort_order', 'label' => 'Order'])
                    <th class="text-end plans-actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td class="fw-medium">{{ $plan->name }}</td>
                        <td><code>{{ $plan->slug }}</code></td>
                        <td>₹{{ number_format($plan->price_monthly, 2) }}</td>
                        <td>{{ $plan->formatLimit($plan->max_posts_per_month) }}</td>
                        <td>
                            <div class="plans-features-cell">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse ($plan->enabledFeatureNames() as $featureName)
                                        <span class="badge bg-light text-dark">{{ $featureName }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $plan->subscriptions_count }}</span></td>
                        <td>
                            @if ($plan->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $plan->sort_order }}</td>
                        <td class="text-end text-nowrap plans-actions-cell">
                            @if (auth('admin')->user()->hasPermission('plans.edit'))
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.plans.toggle-active', $plan) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                        {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                            @if (auth('admin')->user()->hasPermission('plans.delete'))
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('Delete this plan?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No plans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($plans->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span class="small text-muted">Showing {{ $plans->firstItem() }}–{{ $plans->lastItem() }} of {{ $plans->total() }}</span>
            {{ $plans->links() }}
        </div>
    @endif
</div>
@endsection
