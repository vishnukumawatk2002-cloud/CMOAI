<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'CMO AI'))</title>
    @include('layouts.partials.head-assets')
    @stack('styles')
</head>
<body>
    <div class="layout @if($inBrandWorkspace ?? false) layout--brand-workspace @endif">
        @include('layouts.partials.app-sidebar')
        <button type="button" class="sidebar-toggle" id="sidebar-toggle-btn" aria-label="Toggle sidebar" title="Collapse sidebar">
            <i class="ti ti-chevrons-left" id="sidebar-toggle-icon"></i>
        </button>
        @include('layouts.partials.brand-sidebar')
        <main class="main">
            @include('layouts.partials.app-topbar')
            <div class="page-content">
                @if (session('success'))
                    <div class="alert-bar success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-bar error">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('modals')
    @stack('scripts')
</body>
</html>
