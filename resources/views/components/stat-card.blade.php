@props([
    'title',
    'value',
    'subtitle' => null,
    'trend' => null,
    'trendType' => 'up', // up, down, neutral
    'icon' => null,
    'color' => 'emerald', // emerald, blue, amber, purple
])

@php
    $colorClasses = match ($color) {
        'blue' => 'bg-blue-50 text-blue-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'purple' => 'bg-purple-50 text-purple-700',
        'rose' => 'bg-rose-50 text-rose-700',
        default => 'bg-emerald-50 text-emerald-700',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-slate-200/80 p-5 shadow-xs']) }}>
    <div class="flex items-center justify-between">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $title }}</p>
        @if ($icon)
            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $colorClasses }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="mt-2.5">
        <h4 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $value }}</h4>
        @if ($subtitle)
            <p class="text-xs text-slate-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($trend)
        <div class="mt-3 flex items-center gap-1.5 text-xs">
            @if ($trendType === 'up')
                <span class="text-emerald-600 font-semibold flex items-center">↑ {{ $trend }}</span>
            @elseif ($trendType === 'down')
                <span class="text-rose-600 font-semibold flex items-center">↓ {{ $trend }}</span>
            @else
                <span class="text-slate-500 font-medium">{{ $trend }}</span>
            @endif
        </div>
    @endif
</div>
