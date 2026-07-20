@extends('layouts.auth-split')

@section('title', 'Sign up — CMO AI')

@section('leftPanel')
    <div class="left-logo"><div class="left-logo-i"><i class="ti ti-speakerphone"></i></div>CMO <span>AI</span></div>
    <div class="left-content">
        <h2>Start publishing<br>on <em>autopilot</em> today.</h2>
        <p>Join 500+ brands that let CMO AI handle their social media — from content creation to auto-publishing across every platform.</p>
        <div class="left-feats">
            <div class="left-feat"><div class="left-feat-i"><i class="ti ti-cpu"></i></div><span>AI builds your brand knowledge base from your website and files</span></div>
            <div class="left-feat"><div class="left-feat-i"><i class="ti ti-pencil"></i></div><span>Generates posts, carousels, reel scripts in your exact brand voice</span></div>
            <div class="left-feat"><div class="left-feat-i"><i class="ti ti-calendar"></i></div><span>Bulk schedule 30 posts across 7 platforms in one click</span></div>
            <div class="left-feat"><div class="left-feat-i"><i class="ti ti-chart-bar"></i></div><span>AI analytics tell you what to post and exactly when</span></div>
        </div>
    </div>
    <div class="left-foot">No credit card required · Free trial · Cancel anytime</div>
@endsection

@section('content')
    <h1 class="form-h1">Create your account</h1>
    <p class="form-sub">Already have an account? <a href="{{ route('login') }}">Log in</a></p>

    @include('components.google-button')
    <div class="divider">or sign up with email</div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="field-row2">
            <div class="field">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="Jane" required>
                @error('first_name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Doe" required>
                @error('last_name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="field">
            <label for="email"> Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@gmail.com" required>
            <!-- <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="ravi@acmecorp.com" required> -->
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
                <button type="button" class="eye-btn" onclick="togglePw('password')"><i class="ti ti-eye"></i></button>
            </div>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <div class="pw-wrap">
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter password" required>
                <button type="button" class="eye-btn" onclick="togglePw('password_confirmation')"><i class="ti ti-eye"></i></button>
            </div>
        </div>
        <label class="terms">
            <input type="checkbox" name="terms" value="1" required {{ old('terms') ? 'checked' : '' }}>
            <span>I agree to the <a href="{{ route('legal.terms') }}">Terms of Service</a> and <a href="{{ route('legal.privacy') }}">Privacy Policy</a></span>
        </label>
        @error('terms')<span class="field-error">{{ $message }}</span>@enderror
        <button type="submit" class="submit-btn">Create account <i class="ti ti-arrow-right"></i></button>
    </form>
    <div class="bottom-link">Already have an account? <a href="{{ route('login') }}">Log in</a></div>
@endsection

@push('scripts')
<script>function togglePw(id){const p=document.getElementById(id);p.type=p.type==='password'?'text':'password';}</script>
@endpush
