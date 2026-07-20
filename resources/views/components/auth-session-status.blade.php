@props(['status'])

@if ($status)
    <div class="alert alert-success mb-3" role="alert">
        {{ $status }}
    </div>
@endif
