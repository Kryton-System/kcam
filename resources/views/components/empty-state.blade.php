@props([
    'title',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-4 rounded-xl border-2 border-dashed border-slate-200 bg-white/50']) }}>
    @if ($icon)
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500 mb-4">
            {{ $icon }}
        </div>
    @else
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        </div>
    @endif

    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-6 flex justify-center">
            {{ $slot }}
        </div>
    @endif
</div>
