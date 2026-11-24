<x-app-layout>
    <x-slot:title>সম্পাদনা / Edit</x-slot:title>

    <x-slot:header>
        <h1 class="text-xl md:text-2xl font-bold text-slate-900">শিক্ষার্থীর তথ্য সম্পাদনা / Edit student</h1>
        <x-button :href="route('students.show', $student)" variant="secondary">← ফিরে যান / Back</x-button>
    </x-slot:header>

    @php $g = $student->primaryGuardian; @endphp

    <form method="POST" action="{{ route('students.update', $student) }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <x-card title="শিক্ষার্থী / Student" class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input name="name" label="নাম (ইংরেজি) / Name (English)" :value="old('name', $student->name)" required />
                <x-input name="name_bn" label="নাম (বাংলা) / Name (Bangla)" :value="old('name_bn', $student->name_bn)" />
                <x-input name="dob" type="date" label="জন্ম তারিখ / Date of birth" :value="old('dob', $student->dob?->toDateString())" />
                <x-select name="gender" label="লিঙ্গ / Gender" required :selected="old('gender', $student->gender)">
                    <option value="male">ছাত্র / Male</option>
                    <option value="female">ছাত্রী / Female</option>
                    <option value="other">অন্যান্য / Other</option>
                </x-select>
                <x-select name="blood_group" label="রক্তের গ্রুপ / Blood group" :selected="old('blood_group', $student->blood_group)">
                    <option value="">-</option>
                    @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                        <option value="{{ $bg }}">{{ $bg }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="অভিভাবক / Guardian" class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-input name="guardian_name" label="নাম / Name" :value="old('guardian_name', $g?->name)" required />
                <x-select name="guardian_relation" label="সম্পর্ক / Relation" required :selected="old('guardian_relation', $g?->relation ?? 'father')">
                    <option value="father">পিতা / Father</option>
                    <option value="mother">মাতা / Mother</option>
                    <option value="other">অন্যান্য / Other</option>
                </x-select>
                <x-input name="guardian_mobile" label="মোবাইল / Mobile" :value="old('guardian_mobile', $g?->mobile)" required />
            </div>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-button :href="route('students.show', $student)" variant="secondary">বাতিল / Cancel</x-button>
            <x-button type="submit" variant="primary" size="lg">সংরক্ষণ / Save</x-button>
        </div>
    </form>
</x-app-layout>
