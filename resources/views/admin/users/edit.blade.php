@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none small">&larr; Back to user</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <x-input-label for="first_name" value="First Name" />
                    <x-text-input id="first_name" name="first_name" :value="old('first_name', $user->first_name)" required />
                    <x-input-error :messages="$errors->get('first_name')" />
                </div>
                <div class="col-md-6">
                    <x-input-label for="last_name" value="Last Name" />
                    <x-text-input id="last_name" name="last_name" :value="old('last_name', $user->last_name)" required />
                    <x-input-error :messages="$errors->get('last_name')" />
                </div>
                <div class="col-12">
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" :value="old('email', $user->email)" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>
            </div>

            <div class="mt-4">
                <x-primary-button>Save Changes</x-primary-button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
