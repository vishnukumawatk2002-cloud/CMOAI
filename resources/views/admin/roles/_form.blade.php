@php
    $selectedPermissions = old('permissions', isset($role) ? $role->permissions->pluck('id')->toArray() : []);
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <x-input-label for="name" value="Role Name" />
        <x-text-input id="name" name="name" :value="old('name', $role?->name)" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div class="col-md-6">
        <x-input-label for="slug" value="Slug" />
        <x-text-input id="slug" name="slug" :value="old('slug', $role?->slug)" required />
        <x-input-error :messages="$errors->get('slug')" />
    </div>
    <div class="col-12">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $role?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $role?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<h3 class="h6 fw-semibold mb-3">Permissions</h3>
@foreach ($permissions as $group => $groupPermissions)
    <div class="mb-3">
        <div class="text-muted small text-uppercase mb-2">{{ $group }}</div>
        <div class="row g-2">
            @foreach ($groupPermissions as $permission)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}"
                            {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

<div class="mt-4">
    <x-primary-button>{{ isset($role) ? 'Update Role' : 'Create Role' }}</x-primary-button>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
</div>
