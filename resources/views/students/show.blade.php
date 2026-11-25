<x-app-layout>
    <x-slot:title>{{ $student->name }}</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ app()->isLocale('bn') ? ($student->name_bn ?? $student->name) : $student->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ $student->name }} | {{ $student->student_code }}</p>
        </div>
        <div class="flex gap-2">
            <x-button :href="route('students.index')" variant="secondary">← {{ __('students.show.list') }}</x-button>
            @can('edit-students')
                <x-button :href="route('students.edit', $student)" variant="primary">{{ __('students.show.edit') }}</x-button>
            @endcan
        </div>
    </x-slot:header>

    @php
        $g = $student->primaryGuardian;
        $e = $student->currentEnrollment;
        $dash = '-';
    @endphp

    <div class="max-w-3xl space-y-6">
        <x-card :title="__('students.show.section_student')">
            <div class="flex flex-col sm:flex-row gap-6">
                <div class="shrink-0">
                    @if ($student->photo)
                        <img
                            src="{{ route('students.photo', $student) }}"
                            alt="{{ $student->name }}"
                            class="w-32 h-32 object-cover rounded-lg border border-slate-200"
                        >
                    @else
                        <div class="w-32 h-32 rounded-lg border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-xs text-slate-400">
                            {{ __('students.show.no_photo') }}
                        </div>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm flex-1">
                    <div><dt class="text-slate-500">{{ __('students.fields.name_bn') }}</dt><dd class="font-medium text-slate-900">{{ $student->name_bn ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.name') }}</dt><dd class="font-medium text-slate-900">{{ $student->name ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.dob') }}</dt><dd class="font-medium text-slate-900">{{ $student->dob?->format('d/m/Y') ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.gender') }}</dt><dd class="font-medium text-slate-900">{{ __('students.genders.'.$student->gender) }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.blood_group') }}</dt><dd class="font-medium text-slate-900">{{ $student->blood_group ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.religion') }}</dt><dd class="font-medium text-slate-900">{{ $student->religion?->label() ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.birth_reg_no') }}</dt><dd class="font-medium text-slate-900">{{ $student->birth_reg_no ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.class') }}</dt><dd class="font-medium text-slate-900">{{ $e?->schoolClass?->name_bn ?? $e?->schoolClass?->name ?: $dash }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('students.fields.roll_no') }}</dt><dd class="font-medium text-slate-900">{{ $e?->roll_no ?: $dash }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('students.fields.address') }}</dt><dd class="font-medium text-slate-900">{{ $student->address ?: $dash }}</dd></div>
                </dl>
            </div>
        </x-card>

        <x-card :title="__('students.show.section_guardian')">
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500">{{ __('students.fields.guardian_name') }}</dt><dd class="font-medium">{{ $g?->name ?: $dash }}</dd></div>
                <div><dt class="text-slate-500">{{ __('students.fields.guardian_relation') }}</dt><dd class="font-medium">{{ $g?->relation ? __('students.relations.'.$g->relation) : $dash }}</dd></div>
                <div><dt class="text-slate-500">{{ __('students.fields.guardian_mobile') }}</dt><dd class="font-medium">{{ $g?->mobile ?: $dash }}</dd></div>
                <div><dt class="text-slate-500">{{ __('students.fields.guardian_nid') }}</dt><dd class="font-medium">{{ $g?->nid ?: $dash }}</dd></div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">{{ __('students.show.nid_copies') }}</dt>
                    <dd class="font-medium flex flex-wrap gap-3 mt-1">
                        @if ($g?->nid_front_path)
                            <a href="{{ route('students.guardian-nid', [$student, 'front']) }}" target="_blank" rel="noopener" class="text-emerald-700 underline">{{ __('students.show.view_front') }}</a>
                        @endif
                        @if ($g?->nid_back_path)
                            <a href="{{ route('students.guardian-nid', [$student, 'back']) }}" target="_blank" rel="noopener" class="text-emerald-700 underline">{{ __('students.show.view_back') }}</a>
                        @endif
                        @unless ($g?->nid_front_path || $g?->nid_back_path)
                            <span>{{ $dash }}</span>
                        @endunless
                    </dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
