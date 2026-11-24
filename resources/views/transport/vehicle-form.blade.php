@php
    $exists = $vehicle->exists;
@endphp

<x-app-layout>
    <x-slot:title>
        গাড়ি | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                {{ $exists ? 'গাড়ি সম্পাদনা / Edit vehicle' : 'নতুন গাড়ি / New vehicle' }}
            </h1>
        </div>
    </x-slot:header>

    <x-card>
        <form method="POST" action="{{ $exists ? route('transport.vehicles.update', $vehicle) : route('transport.vehicles.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if ($exists)
                @method('PUT')
            @endif

            <x-input name="van_number" label="গাড়ি নম্বর / Van number" :value="old('van_number', $vehicle->van_number)" required />
            <x-input name="driver_name" label="চালকের নাম / Driver name" :value="old('driver_name', $vehicle->driver_name)" required />
            <x-input name="driver_mobile" label="চালকের মোবাইল / Driver mobile" type="tel" inputmode="numeric" :value="old('driver_mobile', $vehicle->driver_mobile)" required />
            <x-input name="capacity" label="ধারণক্ষমতা (আসন) / Capacity (seats)" inputmode="numeric" :value="old('capacity', $vehicle->capacity)" required />

            <div class="flex gap-2">
                <x-button type="submit" variant="primary">সংরক্ষণ / Save</x-button>
                <x-button :href="route('transport.index')" variant="secondary">বাতিল / Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
