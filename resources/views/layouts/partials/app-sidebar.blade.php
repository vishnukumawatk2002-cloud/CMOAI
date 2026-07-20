@php
    $user = auth()->user();
    $brand = $currentBrand ?? null;
    $userBrands = $user ? $user->brands()->orderBy('name')->get() : collect();
    $brandsOpen = request()->routeIs('app.brands.*') || ($inBrandWorkspace ?? false);
    $brandColors = ['#F59E0B', '#3B82F6', '#22C55E', '#EC4899', '#EF4444', '#8B5CF6', '#0EA5E9'];
@endphp
<aside class="sidebar agency-sidebar" id="agency-sidebar">
    <div class="s-logo">
        <div class="s-logo-icon"><i class="ti ti-speakerphone" style="color:#fff;font-size:14px"></i></div>
        <span class="s-logo-text">CMO <span>AI</span></span>
    </div>

    <div class="s-nav">
        <div class="s-label">Menu</div>
        <a href="{{ route('app.dashboard') }}" class="s-item {{ request()->routeIs('app.dashboard') && !($inBrandWorkspace ?? false) ? 'active' : '' }}" data-tip="Dashboard">
            <i class="ti ti-layout-dashboard"></i><span class="s-item-text">Dashboard</span>
        </a>

        <button type="button" class="s-item s-item-toggle {{ request()->routeIs('app.brands.*') || ($inBrandWorkspace ?? false) ? 'active' : '' }}" id="brands-nav-toggle" aria-expanded="{{ $brandsOpen ? 'true' : 'false' }}" data-tip="Brands">
            <i class="ti ti-building-store"></i>
            <span class="s-item-text s-item-label">Brands</span>
            <i class="ti ti-chevron-down s-item-chevron {{ $brandsOpen ? 'open' : '' }}"></i>
        </button>

        <div class="s-brands-dropdown {{ $brandsOpen ? 'open' : '' }}" id="brands-dropdown">
            <div class="s-brands-scroll">
                @forelse ($userBrands as $userBrand)
                    @php
                        $initial = strtoupper(substr($userBrand->name, 0, 1));
                        $color = $brandColors[abs(crc32($userBrand->name)) % count($brandColors)];
                        $isActive = $brand && $brand->id === $userBrand->id && ($inBrandWorkspace ?? false);
                    @endphp
                    <form method="POST" action="{{ route('app.brand.switch', $userBrand->id) }}" class="s-brand-form">
                        @csrf
                        <button type="submit" class="s-brand-option {{ $isActive ? 'active' : '' }}">
                            <span class="s-brand-option-logo" style="background:{{ $color }}">{{ $initial }}</span>
                            <span class="s-brand-option-info">
                                <span class="s-brand-option-name">{{ $userBrand->name }}</span>
                                <span class="s-brand-option-sub">{{ $userBrand->industry ?? 'Brand workspace' }}</span>
                            </span>
                            <span class="s-brand-option-dot {{ $userBrand->is_active ? 'on' : 'off' }}"></span>
                        </button>
                    </form>
                @empty
                    <div class="s-brands-empty">No brands yet</div>
                @endforelse
            </div>
            <a href="{{ route('app.brands.index') }}" class="s-brands-all">
                <i class="ti ti-grid-dots"></i> View all brands
            </a>
        </div>

        <div class="s-label">Management</div>
        <a href="{{ route('app.team.index') }}" class="s-item {{ request()->routeIs('app.team.*') ? 'active' : '' }}" data-tip="Team">
            <i class="ti ti-users"></i><span class="s-item-text">Team</span>
        </a>
        <a href="{{ route('app.reports.index') }}" class="s-item {{ request()->routeIs('app.reports.*') ? 'active' : '' }}" data-tip="Reports">
            <i class="ti ti-chart-bar"></i><span class="s-item-text">Reports</span>
        </a>
    </div>

    <div class="s-bottom">
        <form method="POST" action="{{ route('logout') }}" class="s-logout-form">
            @csrf
            <button type="submit" class="s-logout-btn" data-tip="Logout">
                <i class="ti ti-logout"></i><span class="s-item-text">Logout</span>
            </button>
        </form>
    </div>
</aside>

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('brands-nav-toggle');
        const dropdown = document.getElementById('brands-dropdown');
        if (toggle && dropdown) {
            toggle.addEventListener('click', function () {
                if (document.getElementById('agency-sidebar')?.classList.contains('collapsed')) return;
                const open = dropdown.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.querySelector('.s-item-chevron')?.classList.toggle('open', open);
            });
        }

        const sidebar = document.getElementById('agency-sidebar');
        const sidebarBtn = document.getElementById('sidebar-toggle-btn');
        const sidebarIcon = document.getElementById('sidebar-toggle-icon');
        const storageKey = 'cmo-sidebar-collapsed';

        const setSidebarCollapsed = (collapsed) => {
            if (!sidebar || !sidebarBtn) return;
            sidebar.classList.toggle('collapsed', collapsed);
            sidebarBtn.classList.toggle('is-collapsed', collapsed);
            sidebarBtn.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
            if (sidebarIcon) {
                sidebarIcon.className = collapsed ? 'ti ti-chevrons-right' : 'ti ti-chevrons-left';
            }
            if (collapsed && dropdown) {
                dropdown.classList.remove('open');
                toggle?.setAttribute('aria-expanded', 'false');
                toggle?.querySelector('.s-item-chevron')?.classList.remove('open');
            }
            try {
                localStorage.setItem(storageKey, collapsed ? '1' : '0');
            } catch (e) {}
        };

        if (sidebar && sidebarBtn) {
            let collapsed = false;
            try {
                collapsed = localStorage.getItem(storageKey) === '1';
            } catch (e) {}
            setSidebarCollapsed(collapsed);

            sidebarBtn.addEventListener('click', () => {
                setSidebarCollapsed(!sidebar.classList.contains('collapsed'));
            });
        }
    });
    </script>
    @endpush
@endonce
