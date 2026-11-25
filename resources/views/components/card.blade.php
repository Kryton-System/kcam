@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-slate-200/80 shadow-xs overflow-hidden']) }}>
    @if ($title || $subtitle || $action)
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>

            @if ($action)
                <div>
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100">
            {{ $footer }}
        </div>
    @endif
</div>
