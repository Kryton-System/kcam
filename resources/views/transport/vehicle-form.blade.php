@php
    $exists = $vehicle->exists;
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('transport.vehicle_form.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                {{ $exists ? __('transport.vehicle_form.edit_heading') : __('transport.vehicle_form.new_heading') }}
            </h1>
        </div>
    </x-slot:header>

    <x-card>
        <form method="POST" action="{{ $exists ? route('transport.vehicles.update', $vehicle) : route('transport.vehicles.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if ($exists)
                @method('PUT')
            @endif

            <x-input name="van_number" :label="__('transport.vehicle_form.van_number')" :value="old('van_number', $vehicle->van_number)" required />
            <x-input name="driver_name" :label="__('transport.vehicle_form.driver_name')" :value="old('driver_name', $vehicle->driver_name)" required />
            <x-input name="driver_mobile" :label="__('transport.vehicle_form.driver_mobile')" type="tel" inputmode="numeric" :value="old('driver_mobile', $vehicle->driver_mobile)" required />
            <x-input name="capacity" :label="__('transport.vehicle_form.capacity')" inputmode="numeric" :value="old('capacity', $vehicle->capacity)" required />

            <div class="flex gap-2">
                <x-button type="submit" variant="primary">{{ __('transport.common.save') }}</x-button>
                <x-button :href="route('transport.index')" variant="secondary">{{ __('transport.common.cancel') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
