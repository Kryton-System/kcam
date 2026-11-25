@php
    $visible = collect($actions)->filter(fn (array $action): bool => (bool) $action['show']);
@endphp

@if ($visible->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-6" role="navigation" aria-label="{{ __('dashboard.quick.heading') }}">
        @foreach ($visible as $action)
            <a href="{{ route($action['route']) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-800 hover:border-emerald-500 hover:text-emerald-700 min-h-[40px]">
                {{ $action['label'] }}
            </a>
        @endforeach
    </div>
@endif
