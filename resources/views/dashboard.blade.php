@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Good morning, {{ auth()->user()->first_name }} 👋</h1>
                <p class="text-muted mb-4">You're logged in to CMO AI.</p>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">Edit profile</a>
                    <a href="{{ route('app.dashboard') }}" class="btn btn-success">Go to app dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
