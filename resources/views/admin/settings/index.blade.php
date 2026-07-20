@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<p class="text-muted mb-4">Configure platform-wide settings</p>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    @foreach ($settings as $group => $groupSettings)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0 fw-semibold text-capitalize">{{ str_replace('_', ' ', $group) }}</h2>
            </div>
            <div class="card-body">
                @foreach ($groupSettings as $setting)
                    <div class="mb-3">
                        <x-input-label :for="'setting_'.$setting->key" :value="$setting->label" />
                        @if ($setting->type === 'boolean')
                            <div class="form-check form-switch mt-1">
                                <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="setting_{{ $setting->key }}"
                                    name="settings[{{ $setting->key }}]"
                                    value="1"
                                    {{ old('settings.'.$setting->key, $setting->getCastValue()) ? 'checked' : '' }}>
                            </div>
                        @elseif ($setting->type === 'integer')
                            <input type="number" class="form-control" id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                value="{{ old('settings.'.$setting->key, $setting->value) }}">
                        @else
                            <input type="text" class="form-control" id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                value="{{ old('settings.'.$setting->key, $setting->value) }}">
                        @endif
                        @if ($setting->description)
                            <div class="form-text">{{ $setting->description }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if (auth('admin')->user()->hasPermission('settings.edit'))
        <x-primary-button>Save Settings</x-primary-button>
    @endif
</form>
@endsection
