@extends('layouts.app')

@section('title', 'Generate content — CMO AI')
@section('pageTitle', 'Generate content')

@section('content')
<form method="POST" action="{{ route('app.content.generate') }}" class="body-split">
    @csrf
    <div class="left-col">
        <div class="sec-h">Content type</div>
        <div class="type-grid">
            @foreach(['post' => ['ti-file-text','Post'], 'carousel' => ['ti-layout-grid','Carousel'], 'reel_script' => ['ti-player-play','Reel script'], 'image_caption' => ['ti-photo','Image caption'], 'hashtags' => ['ti-hash','Hashtags'], 'thirty_day_plan' => ['ti-calendar-event','30-day plan']] as $val => [$icon, $label])
            <label class="type-card">
                <input type="radio" name="content_type" value="{{ $val }}" {{ $loop->first ? 'checked' : '' }} style="display:none" onchange="document.querySelectorAll('.type-card').forEach(c=>c.classList.remove('active'));this.closest('.type-card').classList.add('active')">
                <i class="ti {{ $icon }}" style="color:var(--purple2)"></i><span>{{ $label }}</span>
            </label>
            @endforeach
        </div>
        <div class="sec-h">Platform</div>
        <div class="plat-row">
            @foreach(['linkedin','instagram','x','facebook','youtube'] as $p)
            <label class="plat-pill active">
                <input type="checkbox" name="platforms[]" value="{{ $p }}" checked style="display:none" onchange="this.closest('.plat-pill').classList.toggle('active', this.checked)">
                <i class="ti ti-brand-{{ $p === 'x' ? 'x' : $p }}"></i>{{ ucfirst($p) }}
            </label>
            @endforeach
        </div>
        <div class="sec-h">Custom prompt</div>
        <div class="custom-area">
            <textarea name="prompt" rows="4" required placeholder="Describe the content you want CMO AI to create…">{{ old('prompt') }}</textarea>
            @error('prompt')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="gen-btn"><i class="ti ti-sparkles"></i> Generate content</button>
    </div>
    <div class="right-col">
        <div class="empty">
            <i class="ti ti-sparkles" style="color:var(--purple)"></i>
            <h3>Ready to generate</h3>
            <p style="font-size:13px">Select a content type, pick platforms, and hit Generate to create on-brand content in seconds.</p>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.querySelector('.type-card')?.classList.add('active');
</script>
@endpush
