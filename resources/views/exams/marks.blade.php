<x-app-layout>
    <x-slot:title>নম্বর এন্ট্রি / Enter marks</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">নম্বর এন্ট্রি / Enter marks</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ $exam->name_bn ?? $exam->name }} / {{ $exam->name }}</p>
        </div>
        <x-button :href="route('exams.index')" variant="secondary">← পরীক্ষা / Exams</x-button>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <x-select name="subject_id" label="বিষয় / Subject" :selected="$subject?->id">
                    <option value="">-</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->schoolClass?->name_bn ?? $s->schoolClass?->name }} - {{ $s->name_bn ?? $s->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-button type="submit" variant="secondary">দেখুন / Show</x-button>
        </form>
    </x-card>

    @if ($subject)
        <form method="POST" action="{{ route('exams.marks.update', $exam) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="subject_id" value="{{ $subject->id }}">

            <x-card :title="($subject->name_bn ?? $subject->name).' (পূর্ণমান / Full: '.$subject->full_marks.')'">
                @if ($rows->isEmpty())
                    <x-empty-state title="কোনো শিক্ষার্থী নেই / No students" description="" />
                @else
                    <x-table :headers="['রোল / Roll', 'নাম / Name', 'নম্বর / Marks']">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-4 py-3">{{ $row->roll_no ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $row->student->name_bn ?? $row->student->name }}</td>
                                <td class="px-4 py-3">
                                    <input type="text" inputmode="decimal" name="marks[{{ $row->id }}]"
                                           value="{{ old('marks.'.$row->id, $row->current_marks !== null ? rtrim(rtrim((string) $row->current_marks, '0'), '.') : '') }}"
                                           class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm @error('marks.'.$row->id) border-red-500 @enderror">
                                    @error('marks.'.$row->id)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                    <div class="flex justify-end mt-4">
                        <x-button type="submit" variant="primary">সংরক্ষণ / Save</x-button>
                    </div>
                @endif
            </x-card>
        </form>
    @endif
</x-app-layout>
