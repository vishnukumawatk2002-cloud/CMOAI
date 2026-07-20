@extends('layouts.admin')

@section('title', 'Create Permission')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.permissions.index') }}" class="text-decoration-none small">&larr; Back to permissions</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf
            @include('admin.permissions._form')
        </form>
    </div>
</div>
@endsection
