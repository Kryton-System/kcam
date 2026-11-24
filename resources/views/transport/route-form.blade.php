@php
    $exists = $route->exists;
@endphp

<x-app-layout>
    <x-slot:title>
        রুট | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                {{ $exists ? 'রুট সম্পাদনা / Edit route' : 'নতুন রুট / New route' }}
            </h1>
        </div>
    </x-slot:header>

    <x-card>
        <form method="POST" action="{{ $exists ? route('transport.routes.update', $route) : route('transport.routes.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if ($exists)
                @method('PUT')
            @endif

            <x-input name="name" label="রুট / Route (English)" :value="old('name', $route->name)" required />
            <x-input name="name_bn" label="রুটের নাম (বাংলা) / Route name (Bangla)" :value="old('name_bn', $route->name_bn)" />
            <x-textarea name="stops" label="স্টপেজ (প্রতি লাইনে একটি) / Stops (one per line)" rows="5">{{ old('stops', $route->stops) }}</x-textarea>

            <x-select name="vehicle_id" label="গাড়ি / Vehicle" placeholder="নির্বাচন করুন / Choose">
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $route->vehicle_id) === (string) $vehicle->id)>
                        {{ $vehicle->van_number }} ({{ $vehicle->capacity }})
                    </option>
                @endforeach
            </x-select>

            <div class="flex gap-2">
                <x-button type="submit" variant="primary">সংরক্ষণ / Save</x-button>
                <x-button :href="route('transport.index')" variant="secondary">বাতিল / Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
