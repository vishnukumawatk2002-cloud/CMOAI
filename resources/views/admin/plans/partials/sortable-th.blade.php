@php
    $nextDirection = ($sort ?? '') === $column && ($direction ?? 'asc') === 'asc' ? 'desc' : 'asc';
    $isActive = ($sort ?? '') === $column;
@endphp
<th>
    <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]) }}"
       class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
        {{ $label }}
        @if ($isActive)
            <span class="small">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
        @endif
    </a>
</th>
