@php
    use App\Support\Enums\AttendanceStatus;
    use App\Support\Helpers\BanglaHelper;

    $selectedClass = $classes->firstWhere('id', $selectedClassId);
@endphp

<x-app-layout>
    <x-slot:title>
        হাজিরা | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">হাজিরা খাতা</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">শ্রেণি ও তারিখ বেছে প্রতিটি শিক্ষার্থীর হাজিরা দিন</p>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <x-select name="class_id" label="শ্রেণি" :selected="$selectedClassId">
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>
                        {{ $class->name_bn ?? $class->name }}
                    </option>
                @endforeach
            </x-select>

            <x-select name="section_id" label="শাখা" placeholder="সব শাখা" :selected="$selectedSectionId">
                @foreach ($selectedClass?->sections ?? [] as $section)
                    <option value="{{ $section->id }}" @selected($selectedSectionId == $section->id)>
                        {{ $section->name_bn ?? $section->name }}
                    </option>
                @endforeach
            </x-select>

            <x-input name="date" type="date" label="তারিখ" :value="$date" max="{{ now()->toDateString() }}" />

            <div class="flex items-end">
                <x-button type="submit" variant="secondary" class="w-full">দেখুন</x-button>
            </div>
        </form>
    </x-card>

    <x-card>
        @if ($enrollments->isEmpty())
            <x-empty-state
                title="কোনো শিক্ষার্থী পাওয়া যায়নি"
                description="এই শ্রেণি বা শাখায় সক্রিয় ভর্তিকৃত শিক্ষার্থী নেই।"
            />
        @else
            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">

                <p class="text-sm text-slate-600 mb-3">
                    {{ BanglaHelper::formatDate($date) }} | মোট {{ BanglaHelper::toBanglaDigits($enrollments->count()) }} জন
                </p>

                @error('attendance')
                    <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                @enderror
                @error('date')
                    <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                @enderror

                <div class="divide-y divide-slate-100">
                    @foreach ($enrollments as $enrollment)
                        @php($current = $attendanceMap[$enrollment->id] ?? AttendanceStatus::Present->value)
                        <fieldset class="py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                            <legend class="sr-only">{{ $enrollment->student->name }}</legend>
                            <div class="flex-1 font-semibold text-slate-900">
                                <span class="text-slate-500 font-mono text-xs mr-2">
                                    {{ BanglaHelper::toBanglaDigits($enrollment->roll_no ?? '-') }}
                                </span>
                                {{ $enrollment->student->name_bn ?? $enrollment->student->name }}
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach (AttendanceStatus::cases() as $status)
                                    <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 text-sm cursor-pointer min-h-[44px]">
                                        <input
                                            type="radio"
                                            name="attendance[{{ $enrollment->id }}]"
                                            value="{{ $status->value }}"
                                            @checked($current === $status->value)
                                        >
                                        {{ $status->labelBn() }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>

                <div class="mt-5">
                    <x-button type="submit" variant="primary" size="lg" block>হাজিরা সংরক্ষণ করুন</x-button>
                </div>
            </form>
        @endif
    </x-card>
</x-app-layout>
