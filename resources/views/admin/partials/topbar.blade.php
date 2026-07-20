<header class="admin-topbar">
    <div>
        <h1 class="h5 mb-0 fw-semibold">@yield('title', 'Dashboard')</h1>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small">{{ auth('admin')->user()->name }}</span>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">Logout</button>
        </form>
    </div>
</header>
