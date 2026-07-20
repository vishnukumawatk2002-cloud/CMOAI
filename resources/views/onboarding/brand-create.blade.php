@extends('layouts.auth-split')

@section('title', 'Create brand — CMO AI')

@section('content')
    <h1 class="form-h1">Create your brand</h1>
    <p class="form-sub">Tell CMO AI about your brand so it can learn its voice.</p>

    @if ($errors->any())
        <div class="alert-bar error" style="margin-bottom:16px">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('onboarding.brand.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="field-row2">
            <div class="field">
                <label for="name">Brand name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Acme Corp" required>
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" value="{{ old('website') }}" placeholder="https://acmecorp.com">
            </div>
        </div>
        <div class="field-row2">
            <div class="field">
                <label for="industry">Industry *</label>
                <select id="industry" name="industry" required>
                    <option value="">Select…</option>
                    @foreach(['SaaS / Tech','E-commerce','Real estate','Healthcare','Education','Agency','Other'] as $ind)
                        <option value="{{ $ind }}" @selected(old('industry') === $ind)>{{ $ind }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="country">Country *</label>
                <select id="country" name="country" required>
                    @foreach(['India','United States','UAE'] as $c)
                        <option value="{{ $c }}" @selected(old('country', 'India') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="field-row2">
            <div class="field">
                <label for="language">Language</label>
                <select id="language" name="language">
                    @foreach(['English','Hindi','Tamil'] as $lang)
                        <option value="{{ $lang }}" @selected(old('language', 'English') === $lang)>{{ $lang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="tone">Brand tone</label>
                <select id="tone" name="tone">
                    @foreach(['Professional','Casual & friendly','Bold & energetic','Educational'] as $t)
                        <option value="{{ $t }}" @selected(old('tone', 'Professional') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="submit-btn">Create brand & pick plan <i class="ti ti-arrow-right"></i></button>
    </form>
@endsection
