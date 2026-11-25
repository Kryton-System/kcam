@php
    $canManage = auth()->user()->can('manage-transport');
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('transport.index.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('transport.index.title') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('transport.index.subtitle') }}</p>
        </div>
        @if ($canManage)
            <div class="flex gap-2">
                <x-button :href="route('transport.routes.create')" variant="primary">{{ __('transport.index.new_route') }}</x-button>
                <x-button :href="route('transport.vehicles.create')" variant="secondary">{{ __('transport.index.new_vehicle') }}</x-button>
            </div>
        @endif
    </x-slot:header>

    <x-card :title="__('transport.index.routes')" class="mb-6">
        @if ($routes->isEmpty())
            <x-empty-state :title="__('transport.index.no_routes')" :description="__('transport.index.no_routes_hint')" />
        @else
            <x-table :headers="[__('transport.common.route'), __('transport.common.vehicle'), __('transport.common.seats'), '']">
                @foreach ($routes as $route)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $route->name_bn ?? $route->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $route->vehicle?->van_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ \App\Support\Helpers\LocaleFormat::digits((string) $route->assignments_count) }} / {{ $route->vehicle ? \App\Support\Helpers\LocaleFormat::digits((string) $route->vehicle->capacity) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('transport.routes.show', $route) }}" class="text-emerald-700 hover:underline text-sm">{{ __('transport.common.roster') }}</a>
                            @if ($canManage)
                                <a href="{{ route('transport.routes.edit', $route) }}" class="ml-3 text-slate-600 hover:underline text-sm">{{ __('transport.common.edit') }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>

    <x-card :title="__('transport.index.vehicles')">
        @if ($vehicles->isEmpty())
            <x-empty-state :title="__('transport.index.no_vehicles')" :description="__('transport.index.no_vehicles_hint')" />
        @else
            <x-table :headers="[__('transport.index.van_no'), __('transport.common.driver'), __('transport.common.mobile'), __('transport.index.capacity'), '']">
                @foreach ($vehicles as $vehicle)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $vehicle->van_number }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $vehicle->driver_name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $vehicle->driver_mobile }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ \App\Support\Helpers\LocaleFormat::digits((string) $vehicle->capacity) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canManage)
                                <a href="{{ route('transport.vehicles.edit', $vehicle) }}" class="text-slate-600 hover:underline text-sm">{{ __('transport.common.edit') }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
