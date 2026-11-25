<x-app-layout>
    <x-slot:title>{{ __('students.edit.page_title') }}</x-slot:title>

    <x-slot:header>
        <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ __('students.edit.title') }}</h1>
        <x-button :href="route('students.show', $student)" variant="secondary">{{ __('students.edit.back') }}</x-button>
    </x-slot:header>

    @php $g = $student->primaryGuardian; @endphp

    <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @method('PUT')

        <x-card :title="__('students.show.section_student')" class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input name="name" :label="__('students.fields.name')" :value="old('name', $student->name)" required />
                <x-input name="name_bn" :label="__('students.fields.name_bn')" :value="old('name_bn', $student->name_bn)" />
                <x-input name="dob" type="date" :label="__('students.fields.dob')" :value="old('dob', $student->dob?->toDateString())" />
                <x-select name="gender" :label="__('students.fields.gender')" required :selected="old('gender', $student->gender)">
                    <option value="male">{{ __('students.genders.male') }}</option>
                    <option value="female">{{ __('students.genders.female') }}</option>
                    <option value="other">{{ __('students.genders.other') }}</option>
                </x-select>
                <x-select name="blood_group" :label="__('students.fields.blood_group')" :selected="old('blood_group', $student->blood_group)">
                    <option value="">-</option>
                    @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                        <option value="{{ $bg }}">{{ $bg }}</option>
                    @endforeach
                </x-select>
                <x-select name="religion" :label="__('students.fields.religion')" :selected="old('religion', $student->religion?->value)">
                    <option value="">{{ __('students.create.select') }}</option>
                    @foreach (\App\Support\Enums\Religion::cases() as $religion)
                        <option value="{{ $religion->value }}">{{ $religion->label() }}</option>
                    @endforeach
                </x-select>
                <x-input
                    name="birth_reg_no"
                    :label="__('students.fields.birth_reg_no')"
                    inputmode="numeric"
                    maxlength="17"
                    :hint="__('students.hints.birth_reg_no')"
                    :value="old('birth_reg_no', $student->birth_reg_no)"
                />
                <div class="sm:col-span-2">
                    <x-textarea name="address" :label="__('students.fields.address')" rows="2">{{ old('address', $student->address) }}</x-textarea>
                </div>
                <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center gap-4">
                    @if ($student->photo)
                        <img src="{{ route('students.photo', $student) }}" alt="{{ $student->name }}" class="w-24 h-24 object-cover rounded-lg border border-slate-200 shrink-0">
                    @endif
                    <div class="flex-1">
                        <x-input
                            name="photo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            :label="__('students.fields.photo')"
                            :hint="__('students.hints.photo').' '.__('students.edit.keep_current')"
                        />
                    </div>
                </div>
            </div>
        </x-card>

        <x-card :title="__('students.show.section_guardian')" class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-input name="guardian_name" :label="__('students.fields.guardian_name')" :value="old('guardian_name', $g?->name)" required />
                <x-select name="guardian_relation" :label="__('students.fields.guardian_relation')" required :selected="old('guardian_relation', $g?->relation ?? 'father')">
                    <option value="father">{{ __('students.relations.father') }}</option>
                    <option value="mother">{{ __('students.relations.mother') }}</option>
                    <option value="other">{{ __('students.relations.other') }}</option>
                </x-select>
                <x-input name="guardian_mobile" :label="__('students.fields.guardian_mobile')" :value="old('guardian_mobile', $g?->mobile)" required />
                <x-input
                    name="guardian_nid"
                    :label="__('students.fields.guardian_nid')"
                    inputmode="numeric"
                    maxlength="17"
                    :hint="__('students.hints.guardian_nid')"
                    :value="old('guardian_nid', $g?->nid)"
                />
                <div class="sm:col-span-1">
                    <x-input
                        name="guardian_nid_front"
                        type="file"
                        accept="image/jpeg,image/png,application/pdf"
                        :label="__('students.fields.guardian_nid_front')"
                        :hint="__('students.hints.guardian_nid_scan').' '.__('students.edit.keep_current')"
                    />
                </div>
                <div class="sm:col-span-1">
                    <x-input
                        name="guardian_nid_back"
                        type="file"
                        accept="image/jpeg,image/png,application/pdf"
                        :label="__('students.fields.guardian_nid_back')"
                        :hint="__('students.hints.guardian_nid_scan').' '.__('students.edit.keep_current')"
                    />
                </div>
                @if ($g?->nid_front_path || $g?->nid_back_path)
                    <div class="sm:col-span-3 text-sm flex flex-wrap gap-3">
                        @if ($g->nid_front_path)
                            <a href="{{ route('students.guardian-nid', [$student, 'front']) }}" target="_blank" rel="noopener" class="text-emerald-700 underline">{{ __('students.show.view_front') }}</a>
                        @endif
                        @if ($g->nid_back_path)
                            <a href="{{ route('students.guardian-nid', [$student, 'back']) }}" target="_blank" rel="noopener" class="text-emerald-700 underline">{{ __('students.show.view_back') }}</a>
                        @endif
                    </div>
                @endif
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button :href="route('students.show', $student)" variant="secondary">{{ __('students.edit.cancel') }}</x-button>
            <x-button type="submit" variant="primary" size="lg">{{ __('students.edit.save') }}</x-button>
        </div>
    </form>
</x-app-layout>
