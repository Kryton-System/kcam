<x-app-layout>
    <x-slot:title>{{ $student->name }}</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ $student->name_bn ?? $student->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ $student->name }} | {{ $student->student_code }}</p>
        </div>
        <div class="flex gap-2">
            <x-button :href="route('students.index')" variant="secondary">← তালিকা / List</x-button>
            @can('edit-students')
                <x-button :href="route('students.edit', $student)" variant="primary">সম্পাদনা / Edit</x-button>
            @endcan
        </div>
    </x-slot:header>

    @php
        $g = $student->primaryGuardian;
        $e = $student->currentEnrollment;
    @endphp

    <div class="max-w-3xl space-y-6">
        <x-card title="শিক্ষার্থী / Student">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                @foreach ([
                    'নাম (বাংলা) / Name (Bangla)' => $student->name_bn,
                    'নাম (ইংরেজি) / Name (English)' => $student->name,
                    'জন্ম তারিখ / Date of birth' => $student->dob?->format('d/m/Y'),
                    'লিঙ্গ / Gender' => ['male' => 'ছাত্র / Male', 'female' => 'ছাত্রী / Female', 'other' => 'অন্যান্য / Other'][$student->gender] ?? $student->gender,
                    'রক্তের গ্রুপ / Blood group' => $student->blood_group,
                    'শ্রেণি / Class' => $e?->schoolClass?->name_bn ?? $e?->schoolClass?->name,
                    'রোল / Roll' => $e?->roll_no,
                ] as $label => $value)
                    <div>
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <x-card title="অভিভাবক / Guardian">
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500">নাম / Name</dt><dd class="font-medium">{{ $g?->name ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">সম্পর্ক / Relation</dt><dd class="font-medium">{{ $g?->relation ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">মোবাইল / Mobile</dt><dd class="font-medium">{{ $g?->mobile ?: '-' }}</dd></div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
