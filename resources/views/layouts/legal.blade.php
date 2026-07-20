<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Legal') — {{ config('app.name', 'CMO AI') }}</title>
    <meta name="description" content="@yield('meta_description', config('app.name', 'CMO AI').' legal information.')">
    @include('layouts.partials.head-assets', ['landing' => true, 'withScripts' => false])
</head>
<body>
<nav>
    <div class="nav-in">
        <a href="{{ route('landing') }}" class="logo">
            <div class="logo-icon"><i class="ti ti-speakerphone"></i></div>
            <span class="logo-text">CMO <span>AI</span></span>
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('landing') }}#features">Features</a></li>
            <li><a href="{{ route('landing') }}#pricing">Pricing</a></li>
        </ul>
        <div class="nav-btns">
            @auth
                <a href="{{ route('app.dashboard') }}" class="btn btn-outline" style="font-size:13px;padding:8px 16px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ auth()->user()->full_name ?: auth()->user()->email }}
                </a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;margin:0">
                    @csrf
                    <button type="submit" class="btn btn-green" style="font-size:13px;padding:8px 16px">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline" style="font-size:13px;padding:8px 16px">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-green" style="font-size:13px;padding:8px 16px">Start free</a>
            @endauth
        </div>
    </div>
</nav>

<main class="legal-page">
    <div class="container legal-wrap">
        @yield('content')
    </div>
</main>

<footer>
    <div class="foot-in">
        <div class="foot-logo">
            <div class="foot-logo-i"><i class="ti ti-speakerphone" style="color:#fff;font-size:12px"></i></div>
            CMO <span>AI</span>
        </div>
        <div class="foot-links">
            <a href="{{ route('legal.privacy') }}">Privacy</a>
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.data-deletion') }}">Data deletion</a>
            <a href="mailto:hello@cmoai.app">Contact</a>
        </div>
        <span style="color:var(--text3)">&copy; {{ date('Y') }} CMO AI. All rights reserved.</span>
    </div>
</footer>
</body>
</html>
