@props([
    'active' => false,
    'href' => '#',
    'icon' => null,
])

@php
    $classes = $active
        ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold bg-emerald-50 text-emerald-800 transition-colors'
        : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)
        <span class="w-5 h-5 flex items-center justify-center {{ $active ? 'text-emerald-700' : 'text-slate-400' }}">
            {{ $icon }}
        </span>
    @endif
    <span>{{ $slot }}</span>
</a>
