@extends('layouts.admin')

@section('title', 'Create Plan')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.plans.index') }}" class="text-decoration-none small">&larr; Back to plans</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.plans.store') }}">
            @csrf
            @include('admin.plans._form')
        </form>
    </div>
</div>
@endsection
