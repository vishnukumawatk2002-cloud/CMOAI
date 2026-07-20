@extends('layouts.auth-split')

@section('title', 'Reset password — CMO AI')

@section('content')
    <h1 class="form-h1">Reset password</h1>
    <p class="form-sub">Choose a new password for your account.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" required>
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" required>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit" class="submit-btn">Reset password <i class="ti ti-arrow-right"></i></button>
    </form>
@endsection
