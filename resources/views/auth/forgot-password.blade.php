@extends('layouts.auth-split')

@section('title', 'Forgot password — CMO AI')

@section('content')
    <h1 class="form-h1">Forgot password?</h1>
    <p class="form-sub">Enter your email and we'll send a reset link.</p>

    @if (session('status'))
        <div class="alert-bar success" style="margin-bottom:14px">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="submit-btn">Send reset link <i class="ti ti-arrow-right"></i></button>
    </form>
    <div class="bottom-link"><a href="{{ route('login') }}">Back to login</a></div>
@endsection
