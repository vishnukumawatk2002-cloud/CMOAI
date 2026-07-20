<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CMO AI') }}</title>
    @include('layouts.partials.head-assets')
</head>
<body class="bg-light">
    <div class="min-vh-100 d-flex flex-column">
        <header class="py-4 border-bottom bg-white">
            <div class="container">
                <a href="{{ url('/') }}" class="text-decoration-none fw-bold fs-4 text-dark">
                    CMO <span class="text-success">AI</span>
                </a>
            </div>
        </header>

        <main class="flex-grow-1 d-flex align-items-center py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </div>
            </div>
        </main>

        <footer class="py-3 text-center text-muted small border-top bg-white">
            &copy; {{ date('Y') }} CMO AI
        </footer>
    </div>
</body>
</html>
