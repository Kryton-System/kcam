<x-app-layout>
    <x-slot:title>
        {{ __('students.index.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                {{ __('students.index.title') }}
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                {{ __('students.index.subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button :href="route('students.create')" variant="primary" size="md">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('students.index.new_admission') }}
            </x-button>
        </div>
    </x-slot:header>

    <!-- Filters & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <x-input
                    name="search"
                    :placeholder="__('students.index.search_placeholder')"
                    :value="request('search')"
                />
            </div>

            <div>
                <x-select name="class_id" :placeholder="__('students.index.all_classes')" :selected="request('class_id')">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
                            {{ $c->name_bn ?? $c->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <x-select name="status" :selected="request('status')">
                    <option value="">{{ __('students.index.all_statuses') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('students.index.status_active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('students.index.status_inactive') }}</option>
                </x-select>
            </div>

            <div>
                <x-select name="sort" :selected="request('sort', 'name')">
                    <option value="name">{{ __('students.index.sort_name') }}</option>
                    <option value="code">{{ __('students.index.sort_code') }}</option>
                    <option value="newest">{{ __('students.index.sort_newest') }}</option>
                </x-select>
            </div>

            <div class="flex items-center gap-2 lg:col-span-5">
                <x-button type="submit" variant="secondary">
                    {{ __('students.index.filter') }}
                </x-button>
                @if (request()->hasAny(['search', 'class_id', 'status', 'sort']))
                    <a href="{{ route('students.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-2 py-2">
                        {{ __('students.index.reset') }}
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Students Table -->
    <x-card>
        @if ($students->isEmpty())
            <x-empty-state
                :title="__('students.index.empty_title')"
                :description="__('students.index.empty_description')"
            >
                <x-button :href="route('students.create')" variant="primary">
                    {{ __('students.index.admit_student') }}
                </x-button>
            </x-empty-state>
        @else
            <x-table :headers="[__('students.index.columns.roll'), __('students.index.columns.id'), __('students.index.columns.name'), __('students.index.columns.class_section'), __('students.index.columns.guardian_mobile'), __('students.index.columns.status')]">
                @foreach ($students as $student)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-bold text-slate-800">
                            {{ \App\Support\Helpers\LocaleFormat::digits($student->currentEnrollment?->roll_no ?? '-') }}
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-slate-600">
                            {{ $student->student_code }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div><a href="{{ route('students.show', $student) }}" class="hover:text-emerald-700 hover:underline">{{ $student->name_bn ?? $student->name }}</a></div>
                            @if ($student->name_bn && $student->name !== $student->name_bn)
                                <div class="text-[11px] text-slate-400 font-normal">{{ $student->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ $student->currentEnrollment?->schoolClass?->name_bn ?? $student->currentEnrollment?->schoolClass?->name ?? '-' }}
                            @if ($student->currentEnrollment?->section)
                                ({{ $student->currentEnrollment->section->name_bn ?? $student->currentEnrollment->section->name }})
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs font-mono">
                            {{ \App\Support\Helpers\LocaleFormat::digits($student->primaryGuardian?->mobile ?? '-') }}
                        </td>
                        <td class="px-4 py-3">
                            <x-badge variant="success">{{ $student->status?->label() ?? '-' }}</x-badge>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>
