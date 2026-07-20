@extends('layouts.app')

@section('title', 'Edit content — CMO AI')
@section('pageTitle', 'Edit content')

@section('content')
<div class="card" style="max-width:720px">
    <div class="card-title">Edit {{ ucfirst($contentItem->content_type ?? 'post') }}</div>
    <form method="POST" action="{{ route('app.content.update', $contentItem) }}">
        @csrf
        @method('PUT')
        <div class="field-row">
            <div class="field">
                <label for="platform">Platform</label>
                <select id="platform" name="platform">
                    @foreach(['linkedin','instagram','x','facebook','youtube'] as $p)
                        <option value="{{ $p }}" @selected(old('platform', $contentItem->platform) === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    @foreach(['draft','approved','scheduled','published'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $contentItem->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="field">
            <label for="title">Title (optional)</label>
            <input type="text" id="title" name="title" value="{{ old('title', $contentItem->title) }}">
        </div>
        <div class="field">
            <label for="body">Content</label>
            <textarea id="body" name="body" rows="10" required>{{ old('body', $contentItem->body) }}</textarea>
            @error('body')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="scheduled_at">Schedule at (optional)</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                   value="{{ old('scheduled_at', $contentItem->scheduled_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div style="display:flex;gap:10px;margin-top:8px">
            <button type="submit" class="btn btn-green">Save changes</button>
            <a href="{{ route('app.content.library') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
