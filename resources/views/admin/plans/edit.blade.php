@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.plans.index') }}" class="text-decoration-none small">&larr; Back to plans</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
            @csrf
            @method('PUT')
            @include('admin.plans._form', ['plan' => $plan])
        </form>
    </div>
</div>
@endsection
