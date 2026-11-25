<x-app-layout>
    <x-slot:title>
        {{ __('students.create.title') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">
                {{ __('students.create.title') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ __('students.create.subtitle') }}
            </p>
        </div>

        <div>
            <x-button :href="route('students.index')" variant="secondary">
                {{ __('students.create.back') }}
            </x-button>
        </div>
    </x-slot:header>

    <div class="max-w-3xl">
        <p class="text-xs text-slate-500 mb-4">
            <span class="text-red-600 font-bold">*</span> {{ __('students.create.required_hint') }}
        </p>

        <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- 1. Basic information -->
            <x-card :title="__('students.create.section_basic')" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        :label="__('students.fields.name')"
                        :placeholder="__('students.placeholders.name')"
                        :value="old('name')"
                        required
                    />

                    <x-input
                        name="name_bn"
                        :label="__('students.fields.name_bn')"
                        :placeholder="__('students.placeholders.name_bn')"
                        :value="old('name_bn')"
                    />

                    <x-input
                        name="dob"
                        :label="__('students.fields.dob')"
                        type="date"
                        :value="old('dob')"
                    />

                    <x-select name="gender" :label="__('students.fields.gender')" required :selected="old('gender', 'male')">
                        <option value="male">{{ __('students.genders.male') }}</option>
                        <option value="female">{{ __('students.genders.female') }}</option>
                        <option value="other">{{ __('students.genders.other') }}</option>
                    </x-select>

                    <x-select name="blood_group" :label="__('students.fields.blood_group')" :selected="old('blood_group')">
                        <option value="">{{ __('students.create.select') }}</option>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bloodGroup)
                            <option value="{{ $bloodGroup }}">{{ $bloodGroup }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="religion" :label="__('students.fields.religion')" :selected="old('religion')">
                        <option value="">{{ __('students.create.select') }}</option>
                        @foreach (\App\Support\Enums\Religion::cases() as $religion)
                            <option value="{{ $religion->value }}">{{ $religion->label() }}</option>
                        @endforeach
                    </x-select>
                </div>
            </x-card>

            <!-- 2. Academic information -->
            <x-card :title="__('students.create.section_academic')" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-select name="class_id" :label="__('students.fields.class')" required :selected="old('class_id')">
                        <option value="">{{ __('students.create.select_class') }}</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name_bn ?? $c->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="section_id" :label="__('students.create.optional_section')" :selected="old('section_id')">
                        <option value="">{{ __('students.create.select') }}</option>
                        @foreach ($classes as $c)
                            @foreach ($c->sections as $sec)
                                <option value="{{ $sec->id }}">{{ $c->name_bn ?? $c->name }} - {{ $sec->name_bn ?? $sec->name }}</option>
                            @endforeach
                        @endforeach
                    </x-select>

                    <x-input
                        name="roll_no"
                        :label="__('students.fields.roll_no')"
                        type="number"
                        placeholder="1"
                        :value="old('roll_no')"
                    />
                </div>
            </x-card>

            <!-- 3. Guardian information -->
            <x-card :title="__('students.create.section_guardian')" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-input
                        name="guardian_name"
                        :label="__('students.fields.guardian_name')"
                        :placeholder="__('students.placeholders.guardian_name')"
                        :value="old('guardian_name')"
                        required
                    />

                    <x-select name="guardian_relation" :label="__('students.fields.guardian_relation')" required :selected="old('guardian_relation', 'father')">
                        <option value="father">{{ __('students.relations.father') }}</option>
                        <option value="mother">{{ __('students.relations.mother') }}</option>
                        <option value="other">{{ __('students.relations.other') }}</option>
                    </x-select>

                    <x-input
                        name="guardian_nid"
                        :label="__('students.fields.guardian_nid')"
                        inputmode="numeric"
                        maxlength="17"
                        :hint="__('students.hints.guardian_nid')"
                        :value="old('guardian_nid')"
                    />
                </div>
            </x-card>

            <!-- 4. Contact information -->
            <x-card :title="__('students.create.section_contact')" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="guardian_mobile"
                        :label="__('students.fields.guardian_mobile')"
                        :placeholder="__('students.placeholders.mobile')"
                        :hint="__('students.create.guardian_mobile')"
                        inputmode="tel"
                        :value="old('guardian_mobile')"
                        required
                    />
                </div>
            </x-card>

            <!-- 5. Address information -->
            <x-card :title="__('students.create.section_address')" class="mb-6">
                <x-textarea
                    name="address"
                    :label="__('students.fields.address')"
                    rows="2"
                >{{ old('address') }}</x-textarea>
            </x-card>

            <!-- 6. Documents and photograph -->
            <x-card :title="__('students.create.section_documents')" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="birth_reg_no"
                        :label="__('students.fields.birth_reg_no')"
                        inputmode="numeric"
                        maxlength="17"
                        :hint="__('students.hints.birth_reg_no')"
                        :value="old('birth_reg_no')"
                    />

                    <x-input
                        name="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        :label="__('students.fields.photo')"
                        :hint="__('students.hints.photo')"
                    />

                    <x-input
                        name="guardian_nid_front"
                        type="file"
                        accept="image/jpeg,image/png,application/pdf"
                        :label="__('students.fields.guardian_nid_front')"
                        :hint="__('students.hints.guardian_nid_scan')"
                    />

                    <x-input
                        name="guardian_nid_back"
                        type="file"
                        accept="image/jpeg,image/png,application/pdf"
                        :label="__('students.fields.guardian_nid_back')"
                        :hint="__('students.hints.guardian_nid_scan')"
                    />
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button :href="route('students.index')" variant="secondary">
                    {{ __('students.create.cancel') }}
                </x-button>

                <x-button type="submit" variant="primary" size="lg">
                    {{ __('students.create.submit') }}
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
