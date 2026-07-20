@php
    $brand = $currentBrand ?? null;
@endphp
@if (($inBrandWorkspace ?? false) && $brand)
    @php
        $brandInitial = strtoupper(substr($brand->name, 0, 1));
        $brandColor = ['#F59E0B', '#3B82F6', '#22C55E', '#EC4899', '#EF4444', '#8B5CF6', '#0EA5E9'][abs(crc32($brand->name)) % 7];
    @endphp
    <aside class="brand-sidebar">
        <div class="bs-header">
            <div class="bs-logo" style="background:{{ $brandColor }}">{{ $brandInitial }}</div>
            <div class="bs-name" title="{{ $brand->name }}">{{ $brand->name }}</div>
        </div>
        <nav class="bs-nav">
            <div class="bs-label">Brand</div>
            <a href="{{ route('app.brand.dashboard') }}" class="bs-item {{ request()->routeIs('app.brand.dashboard') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>
            <a href="{{ route('app.brand.data-sources') }}" class="bs-item {{ request()->routeIs('app.brand.data-sources') ? 'active' : '' }}">
                <i class="ti ti-database"></i> Brand Data Source
            </a>
            <a href="{{ route('app.brand.knowledge-base') }}" class="bs-item  {{ request()->routeIs('app.brand.knowledge-base') ? 'active' : '' }}">
                <i class="ti ti-brain"></i> Brand Knowledge Base
            </a>
            <a href="{{ route('app.brand.content-suggestions') }}" class="bs-item  {{ request()->routeIs('app.brand.content-suggestions') ? 'active' : '' }}">
                <i class="ti ti-bulb"></i> Brand content suggestions
            </a>
            <a href="{{ route('app.brand.content-library') }}" class="bs-item {{ request()->routeIs('app.brand.content-library*') ? 'active' : '' }}">
                <i class="ti ti-folder"></i> Content Library
            </a>
            <a href="{{ route('app.brand.post-planning') }}" class="bs-item {{ request()->routeIs('app.brand.post-planning*') && ! request()->routeIs('app.brand.ai-post-library*') ? 'active' : '' }}">
                <i class="ti ti-calendar-event"></i> Post planning
            </a>
            <a href="{{ route('app.brand.ai-post-library') }}" class="bs-item {{ request()->routeIs('app.brand.ai-post-library*') ? 'active' : '' }}">
                <i class="ti ti-layout-grid"></i>  Post Library
            </a>
            <!-- <a href="{{ route('app.brand.ai-post-library') }}" class="bs-item {{ request()->routeIs('app.brand.ai-post-library*') ? 'active' : '' }}">
                <i class="ti ti-layout-grid"></i> Ai Post Library
            </a> -->
            <!-- <a href="{{ route('app.content.library') }}" class="bs-item {{ request()->routeIs('app.content.library', 'app.content.edit', 'app.content.update') ? 'active' : '' }}">
                <i class="ti ti-files"></i> Post Library
            </a> -->
            <a href="{{ route('app.schedule.index') }}" class="bs-item {{ request()->routeIs('app.schedule.*') ? 'active' : '' }}">
                <i class="ti ti-calendar"></i> Schedule
            </a>
            <a href="{{ route('app.analytics') }}" class="bs-item {{ request()->routeIs('app.analytics') ? 'active' : '' }}">
                <i class="ti ti-chart-bar"></i> Analytics
            </a>
            <!-- <a href="{{ route('app.ai-generator.index') }}" class="bs-item  {{ request()->routeIs('app.ai-generator.*') ? 'active' : '' }}">
                <i class="ti ti-sparkles"></i> AI Generator
            </a> -->
            <!-- <a href="{{ route('app.content.generate') }}" class="bs-item {{ request()->routeIs('app.content.generate') ? 'active' : '' }}">
                <i class="ti ti-sparkles"></i> Generate content
            </a> -->
            <div class="bs-divider"></div>
            <a href="{{ route('app.brand.settings') }}" class="bs-item {{ request()->routeIs('app.brand.settings*') ? 'active' : '' }}">
                <i class="ti ti-settings"></i> Brand settings
            </a>
            <a href="{{ route('app.brand.social-accounts') }}" class="bs-item {{ request()->routeIs('app.brand.social-accounts') ? 'active' : '' }}">
                <i class="ti ti-plug"></i> Social accounts
            </a>
            <a href="{{ route('app.brands.show', $brand) }}" class="bs-item {{ request()->routeIs('app.brands.show') ? 'active' : '' }}">
                <i class="ti ti-eye"></i> Brand profile
            </a>
        </nav>
    </aside>
@endif
