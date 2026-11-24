@php
    use App\Support\Helpers\BanglaHelper;
    $canManage = auth()->user()->can('manage-transport');
@endphp

<x-app-layout>
    <x-slot:title>
        পরিবহন | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">পরিবহন / Transport</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">রুট ও গাড়ি / Routes and vehicles</p>
        </div>
        @if ($canManage)
            <div class="flex gap-2">
                <x-button :href="route('transport.routes.create')" variant="primary">নতুন রুট / New route</x-button>
                <x-button :href="route('transport.vehicles.create')" variant="secondary">নতুন গাড়ি / New vehicle</x-button>
            </div>
        @endif
    </x-slot:header>

    <x-card title="রুট / Routes" class="mb-6">
        @if ($routes->isEmpty())
            <x-empty-state title="কোনো রুট নেই / No routes" description="রুট যোগ করলে এখানে দেখা যাবে / Routes appear here once added." />
        @else
            <x-table :headers="['রুট / Route', 'গাড়ি / Vehicle', 'আসন / Seats', '']">
                @foreach ($routes as $route)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $route->name_bn ?? $route->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $route->vehicle?->van_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ BanglaHelper::toBanglaDigits((string) $route->assignments_count) }} / {{ $route->vehicle ? BanglaHelper::toBanglaDigits((string) $route->vehicle->capacity) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('transport.routes.show', $route) }}" class="text-emerald-700 hover:underline text-sm">তালিকা / Roster</a>
                            @if ($canManage)
                                <a href="{{ route('transport.routes.edit', $route) }}" class="ml-3 text-slate-600 hover:underline text-sm">সম্পাদনা / Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>

    <x-card title="গাড়ি / Vehicles">
        @if ($vehicles->isEmpty())
            <x-empty-state title="কোনো গাড়ি নেই / No vehicles" description="গাড়ি যোগ করলে এখানে দেখা যাবে / Vehicles appear here once added." />
        @else
            <x-table :headers="['গাড়ি নম্বর / Van no.', 'চালক / Driver', 'মোবাইল / Mobile', 'ধারণক্ষমতা / Capacity', '']">
                @foreach ($vehicles as $vehicle)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $vehicle->van_number }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $vehicle->driver_name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $vehicle->driver_mobile }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ BanglaHelper::toBanglaDigits((string) $vehicle->capacity) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canManage)
                                <a href="{{ route('transport.vehicles.edit', $vehicle) }}" class="text-slate-600 hover:underline text-sm">সম্পাদনা / Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
