@php
    use App\Support\Helpers\BanglaHelper;
    $canManage = auth()->user()->can('manage-transport');
    $capacity = $route->vehicle?->capacity;
    $totalFee = $assignments->sum('monthly_fee');
@endphp

<x-app-layout>
    <x-slot:title>
        {{ $route->name }} | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ $route->name_bn ?? $route->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                {{ $route->vehicle ? $route->vehicle->van_number.' | '.$route->vehicle->driver_name.' | '.$route->vehicle->driver_mobile : 'গাড়ি নেই / No vehicle' }}
            </p>
        </div>
        <div class="flex gap-2">
            <x-button :href="route('transport.routes.print', $route)" variant="outline" target="_blank">প্রিন্ট / Print</x-button>
            @if ($canManage)
                <x-button :href="route('transport.routes.edit', $route)" variant="secondary">সম্পাদনা / Edit</x-button>
            @endif
        </div>
    </x-slot:header>

    <div class="grid grid-cols-2 gap-3 mb-6">
        <x-card>
            <p class="text-xs text-slate-500">আসন / Seats</p>
            <p class="text-lg font-bold">{{ BanglaHelper::toBanglaDigits((string) $assignments->count()) }} / {{ $capacity !== null ? BanglaHelper::toBanglaDigits((string) $capacity) : '-' }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-slate-500">মাসিক মোট / Monthly total</p>
            <p class="text-lg font-bold">{{ BanglaHelper::formatTaka($totalFee, true) }}</p>
        </x-card>
    </div>

    @if ($route->stopList())
        <x-card title="স্টপেজ / Stops" class="mb-6">
            <ol class="list-decimal pl-5 text-sm text-slate-700 space-y-1">
                @foreach ($route->stopList() as $stopName)
                    <li>{{ $stopName }}</li>
                @endforeach
            </ol>
        </x-card>
    @endif

    @if ($canManage)
        <x-card title="শিক্ষার্থী যোগ / Assign student" class="mb-6">
            <form method="POST" action="{{ route('transport.routes.assign', $route) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-start">
                @csrf
                <x-select name="enrollment_id" label="শিক্ষার্থী / Student" placeholder="নির্বাচন করুন / Choose" required>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}" @selected((string) old('enrollment_id') === (string) $candidate->id)>
                            {{ $candidate->student->name_bn ?? $candidate->student->name }} ({{ $candidate->schoolClass?->name_bn ?? $candidate->schoolClass?->name }})
                        </option>
                    @endforeach
                </x-select>
                <x-input name="stop" label="স্টপ / Stop" :value="old('stop')" />
                <x-input name="monthly_fee" label="মাসিক ভাড়া (৳) / Monthly fee" inputmode="decimal" :value="old('monthly_fee')" required />
                <div class="sm:pt-7">
                    <x-button type="submit" variant="primary">যোগ করুন / Assign</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card title="তালিকা / Roster">
        @if ($assignments->isEmpty())
            <x-empty-state title="কোনো শিক্ষার্থী নেই / No students" description="শিক্ষার্থী যোগ করলে এখানে দেখা যাবে / Assigned students appear here." />
        @else
            <x-table :headers="['শিক্ষার্থী / Student', 'শ্রেণি / Class', 'স্টপ / Stop', 'মাসিক ভাড়া / Fee', '']">
                @foreach ($assignments as $assignment)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $assignment->enrollment->student->name_bn ?? $assignment->enrollment->student->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $assignment->enrollment->schoolClass?->name_bn ?? $assignment->enrollment->schoolClass?->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $assignment->stop ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ BanglaHelper::formatTaka($assignment->monthly_fee, true) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canManage)
                                <form method="POST" action="{{ route('transport.assignments.destroy', $assignment) }}" onsubmit="return confirm('সরাতে চান? / Remove this student?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">সরান / Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
