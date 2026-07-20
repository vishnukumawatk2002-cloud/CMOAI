@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card stat-card-primary border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/></svg>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stats['total_users']) }}</div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card stat-card-success border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stats['total_orders']) }}</div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card stat-card-warning border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-1.236-2.542-3.994-3.004C4.978 7.324 4.5 6.865 4.5 5.978c0-1.074.966-1.865 2.362-1.865 1.166 0 2.052.612 2.314 1.5h1.403c-.192-1.858-1.657-3.172-3.717-3.172C5.62 1.5 4 2.838 4 5.154c0 1.597 1.236 2.542 3.994 3.004 2.273.282 3.678 1.438 3.678 3.3 0 1.674-1.482 2.854-3.678 3.004v1.216h1.043c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-1.236-2.542-3.994-3.004C4.978 10.324 4 9.865 4 8.978 4 7.904 4.966 7.113 6.362 7.113c1.166 0 2.052.612 2.314 1.5H10.08z"/></svg>
                </div>
                <div>
                    <div class="stat-value">₹{{ number_format($stats['total_revenue'], 0) }}</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h2 class="h6 fw-semibold mb-0">Revenue & Orders (Monthly)</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h2 class="h6 fw-semibold mb-0">New Users (Monthly)</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-semibold mb-0">Recent Activities</h2>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush activity-list">
                    @forelse ($activities as $activity)
                        <li class="list-group-item d-flex align-items-start gap-3 py-3">
                            <span class="activity-dot bg-{{ $activity['color'] }}"></span>
                            <div class="flex-grow-1">
                                <p class="mb-0 small">{{ $activity['message'] }}</p>
                                <span class="text-muted" style="font-size: 0.75rem;">{{ $activity['created_at']->diffForHumans() }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-4">No recent activity.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-semibold mb-0">Latest Users</h2>
                @if (auth('admin')->user()->hasPermission('users.view'))
                    <a href="{{ route('admin.users.index') }}" class="small text-decoration-none">View all</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestUsers as $user)
                            <tr>
                                <td class="fw-medium">
                                    @if (auth('admin')->user()->hasPermission('users.view'))
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none">{{ $user->full_name }}</a>
                                    @else
                                        {{ $user->full_name }}
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $user->email }}</td>
                                <td class="small text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No users yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.dashboardCharts = @json($charts);
</script>
<script defer src="{{ asset('js/admin-dashboard.js') }}?v={{ filemtime(public_path('js/admin-dashboard.js')) }}"></script>
@endpush
