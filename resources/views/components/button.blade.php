@props([
    'variant' => 'primary',
    'size' => 'md',
    'block' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 touch-target disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $sizeClasses = match ($size) {
        'sm' => 'px-3 py-2 text-sm min-h-[40px]',
        'lg' => 'px-6 py-3.5 text-base min-h-[52px]',
        default => 'px-4 py-2.5 text-sm min-h-[48px]',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-slate-100 text-slate-800 hover:bg-slate-200 focus:ring-slate-400 border border-slate-200',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-400',
        'outline' => 'bg-transparent text-emerald-700 border-2 border-emerald-600 hover:bg-emerald-50 focus:ring-emerald-500',
        default => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 shadow-sm',
    };

    $widthClass = $block ? 'w-full' : '';

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses} {$widthClass}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
