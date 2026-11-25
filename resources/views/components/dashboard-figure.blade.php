@props([
    'label',
    'value',
    'note' => null,
    'tone' => 'neutral', // neutral, success, warning
])

@php
    $accent = match ($tone) {
        'success' => 'border-l-emerald-600',
        'warning' => 'border-l-amber-500',
        default => 'border-l-slate-300',
    };
@endphp

<div {{ $attributes->merge(['class' => "bg-white border border-slate-200 border-l-4 {$accent} rounded-lg px-4 py-3"]) }}>
    <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-xl font-semibold text-slate-900 tabular-nums">{{ $value }}</p>
    @if ($note)
        <p class="mt-0.5 text-xs text-slate-500">{{ $note }}</p>
    @endif
</div>
