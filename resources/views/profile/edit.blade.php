@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 fw-bold mb-4">{{ __('Profile') }}</h1>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card shadow-sm border-0 border-danger">
            <div class="card-body p-4">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
