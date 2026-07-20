@php
    $landing = $landing ?? false;
    $withScripts = $withScripts ?? true;
    $withAdminDashboard = $withAdminDashboard ?? false;

    $v = fn (string $path) => file_exists(public_path($path))
        ? filemtime(public_path($path))
        : '1';
@endphp
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ $v('favicon.png') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ $v('favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ $v('favicon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">

@if ($landing)
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ $v('css/landing.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}?v={{ $v('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cmo-design.css') }}?v={{ $v('css/cmo-design.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ $v('css/app.css') }}">
@endif

@if ($withScripts)
    <script defer src="{{ asset('js/bootstrap.bundle.min.js') }}?v={{ $v('js/bootstrap.bundle.min.js') }}"></script>
    <script defer src="{{ asset('js/app.js') }}?v={{ $v('js/app.js') }}"></script>
@endif

@if ($withAdminDashboard)
    <script defer src="{{ asset('js/admin-dashboard.js') }}?v={{ $v('js/admin-dashboard.js') }}"></script>
@endif
