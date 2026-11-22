@props([
    'variant' => 'neutral', // success, danger, warning, info, neutral
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'px-2 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
        default => 'px-2.5 py-0.5 text-xs',
    };

    $variantClasses = match ($variant) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10',
        'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
        'info' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10',
        default => 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-600/10',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-md {$sizeClasses} {$variantClasses}"]) }}>
    {{ $slot }}
</span>
