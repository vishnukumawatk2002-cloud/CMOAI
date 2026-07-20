@extends('layouts.auth-split')

@section('title', 'Log in — CMO AI')

@section('leftPanel')
    <div class="left-logo"><div class="left-logo-i"><i class="ti ti-speakerphone"></i></div>CMO <span>AI</span></div>
    <div class="left-content">
        <div class="stars">★★★★★</div>
        <p class="quote">"CMO AI cut our content workload by 80%. Our LinkedIn reach tripled in the first month. It genuinely understands our brand voice better than most humans."</p>
        <div class="author">
            <div class="av">PR</div>
            <div>
                <div class="author-name">Priya Rajan</div>
                <div class="author-role">Head of Marketing, NovaSoft India</div>
            </div>
        </div>
    </div>
    <div class="left-foot">&copy; {{ date('Y') }} CMO AI</div>
@endsection

<style>

    .submit-btn:hover {
    background: #6c63ff !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 20px #6c63ff00 !important;
}
</style>

@section('content')
    <h1 class="form-h1">Welcome back</h1>
    <p class="form-sub">Don't have an account? <a href="{{ route('register') }}">Sign up free</a></p>

    @if (session('status'))
        <div class="alert-bar success" style="margin-bottom:14px">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-bar error" style="margin-bottom:14px">{{ session('error') }}</div>
    @endif

    @include('components.google-button')
    <div class="divider">or log in with email</div>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" 
            placeholder="you@gmail.com" required autofocus>
            <!-- <input type="email" id="email" name="email" value="{{ old('email') }}" 
            placeholder="ravi@acmecorp.com" required autofocus> -->
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="eye-btn" onclick="togglePw('password')"><i class="ti ti-eye"></i></button>
            </div>
            <div class="field-foot bottom-link" style="text-align: left;">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <label class="terms">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <span>Remember me</span>
        </label>
        <button type="submit" class="submit-btn">Log in <i class="ti ti-arrow-right"></i></button>
    </form>
    <div class="bottom-link">New to CMO AI? <a href="{{ route('register') }}">Create a free account</a></div>
@endsection

@push('scripts')
<script>function togglePw(id){const p=document.getElementById(id);p.type=p.type==='password'?'text':'password';}</script>
@endpush
