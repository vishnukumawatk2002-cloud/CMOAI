@php $permission = $permission ?? null; @endphp
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <x-input-label for="name" value="Permission Name" />
        <x-text-input id="name" name="name" :value="old('name', $permission?->name)" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div class="col-md-6">
        <x-input-label for="slug" value="Slug" />
        <x-text-input id="slug" name="slug" :value="old('slug', $permission?->slug)" required placeholder="e.g. users.view" />
        <x-input-error :messages="$errors->get('slug')" />
    </div>
    <div class="col-md-6">
        <x-input-label for="group" value="Group" />
        <x-text-input id="group" name="group" :value="old('group', $permission?->group ?? 'general')" required />
        <x-input-error :messages="$errors->get('group')" />
    </div>
    <div class="col-12">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $permission?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>
</div>

<div>
    <x-primary-button>{{ isset($permission) ? 'Update Permission' : 'Create Permission' }}</x-primary-button>
    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
</div>
