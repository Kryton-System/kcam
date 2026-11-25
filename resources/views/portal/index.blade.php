<x-app-layout>
    <x-slot:title>{{ __('portal.title') }}</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ __('portal.my_children') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('portal.title') }}</p>
        </div>
    </x-slot:header>

    @if ($children->isEmpty())
        <x-card>
            <p class="text-sm text-slate-600">{{ __('portal.index.empty') }}</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($children as $child)
                <a href="{{ route('portal.children.show', $child) }}" class="block">
                    <x-card class="hover:border-emerald-400 transition">
                        <div class="p-5">
                            <h2 class="font-bold text-slate-900">{{ $child->name_bn ?? $child->name }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $child->name }} | {{ $child->student_code }}</p>
                            <p class="text-sm text-slate-700 mt-3">
                                {{ __('portal.index.class') }}: {{ $child->currentEnrollment?->schoolClass?->name_bn ?? $child->currentEnrollment?->schoolClass?->name ?? '-' }}
                                | {{ __('portal.index.roll') }}: {{ $child->currentEnrollment?->roll_no ?? '-' }}
                            </p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
