<x-app-layout>
    <x-slot:title>
        শিক্ষার্থী তালিকা | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                শিক্ষার্থী তালিকা
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                সকল ভর্তিকৃত শিক্ষার্থীর তথ্য ও তালিকা
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button :href="route('students.create')" variant="primary" size="md">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                নতুন ভর্তি
            </x-button>
        </div>
    </x-slot:header>

    <!-- Filters & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <x-input
                    name="search"
                    placeholder="নাম অথবা আইডি দিয়ে খুঁজুন..."
                    :value="request('search')"
                />
            </div>

            <div>
                <x-select name="class_id" placeholder="সব শ্রেণি" :selected="request('class_id')">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
                            {{ $c->name_bn ?? $c->name }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="secondary" class="flex-1">
                    ফিল্টার করুন
                </x-button>
                @if (request()->hasAny(['search', 'class_id']))
                    <a href="{{ route('students.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-2">
                        রিসেট
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Students Table -->
    <x-card>
        @if ($students->isEmpty())
            <x-empty-state
                title="কোনো শিক্ষার্থী পাওয়া যায়নি"
                description="নতুন শিক্ষার্থী যোগ করতে উপরের 'নতুন ভর্তি' বাটনে ক্লিক করুন।"
            >
                <x-button :href="route('students.create')" variant="primary">
                    শিক্ষার্থী ভর্তি করুন
                </x-button>
            </x-empty-state>
        @else
            <x-table :headers="['রোল', 'আইডি', 'নাম', 'শ্রেণি ও শাখা', 'অভিভাবকের মোবাইল', 'অবস্থা']">
                @foreach ($students as $student)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3 font-bold text-slate-800">
                            {{ \App\Support\Helpers\BanglaHelper::toBanglaDigits($student->currentEnrollment?->roll_no ?? '-') }}
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-slate-600">
                            {{ $student->student_code }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div>{{ $student->name_bn ?? $student->name }}</div>
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
                            {{ \App\Support\Helpers\BanglaHelper::toBanglaDigits($student->primaryGuardian?->mobile ?? '-') }}
                        </td>
                        <td class="px-4 py-3">
                            <x-badge variant="success">{{ $student->status?->labelBn() ?? 'সক্রিয়' }}</x-badge>
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
