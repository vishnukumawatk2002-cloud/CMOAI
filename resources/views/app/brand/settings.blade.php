@extends('layouts.app')

@section('title', 'Brand settings — CMO AI')
@section('pageTitle', 'Brand settings')

@section('content')
<div class="card" style="max-width:640px">
    <div class="card-title">General settings</div>
    <form method="POST" action="{{ route('app.brand.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="field-row">
            <div class="field">
                <label for="name">Brand name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $brand->name) }}" required>
            </div>
            <div class="field">
                <label for="website">Website</label>
                <input type="url" id="website" name="website" value="{{ old('website', $brand->website) }}">
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="industry">Industry</label>
                <input type="text" id="industry" name="industry" value="{{ old('industry', $brand->industry) }}">
            </div>
            <div class="field">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', $brand->country) }}">
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="language">Language</label>
                <input type="text" id="language" name="language" value="{{ old('language', $brand->language) }}">
            </div>
            <div class="field">
                <label for="tone">Brand tone</label>
                <input type="text" id="tone" name="tone" value="{{ old('tone', $brand->tone) }}">
            </div>
        </div>
        <button type="submit" class="btn btn-green">Save settings</button>
    </form>
</div>

<div class="card settings-danger-zone" style="max-width:640px;margin-top:16px">
    <div class="card-title" style="color:var(--danger)">Delete brand</div>
    <p style="font-size:13px;color:var(--text2);line-height:1.6;margin-bottom:16px">
        Permanently remove <strong>{{ $brand->name }}</strong> and all related data — content, assets, social accounts, and AI knowledge base. This cannot be undone.
    </p>
    <form method="POST" action="{{ route('app.brand.settings.destroy') }}" onsubmit="return confirm('Are you sure you want to delete ' + @json($brand->name) + '? This action cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i> Delete brand</button>
    </form>
</div>
@endsection
