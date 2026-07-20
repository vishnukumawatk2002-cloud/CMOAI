@extends('layouts.admin')

@section('title', 'Edit Role')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.roles.index') }}" class="text-decoration-none small">&larr; Back to roles</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('admin.roles._form', ['role' => $role])
        </form>
    </div>
</div>
@endsection
