@php
    $exists = $route->exists;
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('transport.route_form.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                {{ $exists ? __('transport.route_form.edit_heading') : __('transport.route_form.new_heading') }}
            </h1>
        </div>
    </x-slot:header>

    <x-card>
        <form method="POST" action="{{ $exists ? route('transport.routes.update', $route) : route('transport.routes.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if ($exists)
                @method('PUT')
            @endif

            <x-input name="name" :label="__('transport.route_form.name_en')" :value="old('name', $route->name)" required />
            <x-input name="name_bn" :label="__('transport.route_form.name_bn')" :value="old('name_bn', $route->name_bn)" />
            <x-textarea name="stops" :label="__('transport.route_form.stops')" rows="5">{{ old('stops', $route->stops) }}</x-textarea>

            <x-select name="vehicle_id" :label="__('transport.common.vehicle')" :placeholder="__('transport.show.choose')">
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $route->vehicle_id) === (string) $vehicle->id)>
                        {{ $vehicle->van_number }} ({{ $vehicle->capacity }})
                    </option>
                @endforeach
            </x-select>

            <div class="flex gap-2">
                <x-button type="submit" variant="primary">{{ __('transport.common.save') }}</x-button>
                <x-button :href="route('transport.index')" variant="secondary">{{ __('transport.common.cancel') }}</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
