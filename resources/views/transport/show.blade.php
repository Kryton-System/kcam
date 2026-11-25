@php
    $canManage = auth()->user()->can('manage-transport');
    $canSeeFees = $canManage;
    $capacity = $route->vehicle?->capacity;
    $totalFee = $assignments->sum('monthly_fee');
@endphp

<x-app-layout>
    <x-slot:title>
        {{ $route->name }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ $route->name_bn ?? $route->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                {{ $route->vehicle ? $route->vehicle->van_number.' | '.$route->vehicle->driver_name.' | '.$route->vehicle->driver_mobile : __('transport.common.no_vehicle') }}
            </p>
        </div>
        <div class="flex gap-2">
            <x-button :href="route('transport.routes.print', $route)" variant="outline" target="_blank">{{ __('transport.common.print') }}</x-button>
            @if ($canManage)
                <x-button :href="route('transport.routes.edit', $route)" variant="secondary">{{ __('transport.common.edit') }}</x-button>
            @endif
        </div>
    </x-slot:header>

    <div class="grid {{ $canSeeFees ? 'grid-cols-2' : 'grid-cols-1' }} gap-3 mb-6">
        <x-card>
            <p class="text-xs text-slate-500">{{ __('transport.common.seats') }}</p>
            <p class="text-lg font-bold">{{ \App\Support\Helpers\LocaleFormat::digits((string) $assignments->count()) }} / {{ $capacity !== null ? \App\Support\Helpers\LocaleFormat::digits((string) $capacity) : '-' }}</p>
        </x-card>
        @if ($canSeeFees)
            <x-card>
                <p class="text-xs text-slate-500">{{ __('transport.show.monthly_total') }}</p>
                <p class="text-lg font-bold">{{ \App\Support\Helpers\LocaleFormat::taka($totalFee, true) }}</p>
            </x-card>
        @endif
    </div>

    @if ($route->stopList())
        <x-card :title="__('transport.common.stops')" class="mb-6">
            <ol class="list-decimal pl-5 text-sm text-slate-700 space-y-1">
                @foreach ($route->stopList() as $stopName)
                    <li>{{ $stopName }}</li>
                @endforeach
            </ol>
        </x-card>
    @endif

    @if ($canManage)
        <x-card :title="__('transport.show.assign_student')" class="mb-6">
            <form method="POST" action="{{ route('transport.routes.assign', $route) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-start">
                @csrf
                <x-select name="enrollment_id" :label="__('transport.common.student')" :placeholder="__('transport.show.choose')" required>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}" @selected((string) old('enrollment_id') === (string) $candidate->id)>
                            {{ $candidate->student->name_bn ?? $candidate->student->name }} ({{ $candidate->schoolClass?->name_bn ?? $candidate->schoolClass?->name }})
                        </option>
                    @endforeach
                </x-select>
                <x-input name="stop" :label="__('transport.common.stop')" :value="old('stop')" />
                <x-input name="monthly_fee" :label="__('transport.show.monthly_fee_label')" inputmode="decimal" :value="old('monthly_fee')" required />
                <div class="sm:pt-7">
                    <x-button type="submit" variant="primary">{{ __('transport.show.assign') }}</x-button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card :title="__('transport.common.roster')">
        @if ($assignments->isEmpty())
            <x-empty-state :title="__('transport.show.no_students')" :description="__('transport.show.no_students_hint')" />
        @else
            <x-table :headers="$canSeeFees ? [__('transport.common.student'), __('transport.common.class'), __('transport.common.stop'), __('transport.show.fee'), ''] : [__('transport.common.student'), __('transport.common.class'), __('transport.common.stop'), '']">
                @foreach ($assignments as $assignment)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $assignment->enrollment->student->name_bn ?? $assignment->enrollment->student->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $assignment->enrollment->schoolClass?->name_bn ?? $assignment->enrollment->schoolClass?->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $assignment->stop ?? '-' }}</td>
                        @if ($canSeeFees)
                            <td class="px-4 py-3 text-slate-700">{{ \App\Support\Helpers\LocaleFormat::taka($assignment->monthly_fee, true) }}</td>
                        @endif
                        <td class="px-4 py-3 text-right">
                            @if ($canManage)
                                <form method="POST" action="{{ route('transport.assignments.destroy', $assignment) }}" onsubmit="return confirm('{{ __('transport.show.confirm_remove') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">{{ __('transport.show.remove') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
