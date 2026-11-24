<x-app-layout>
    <x-slot:title>
        পরীক্ষা | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">পরীক্ষা ও ফলাফল</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">চলতি শিক্ষাবর্ষের পরীক্ষার তালিকা</p>
        </div>
    </x-slot:header>

    <x-card>
        @if ($exams->isEmpty())
            <x-empty-state title="কোনো পরীক্ষা নেই" description="পরীক্ষা যোগ করার সুবিধা শীঘ্রই আসছে।" />
        @else
            <x-table :headers="['পরীক্ষা', 'শুরু', 'শেষ', 'ফলাফল', '']">
                @foreach ($exams as $exam)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $exam->name_bn ?? $exam->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $exam->starts_on?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $exam->ends_on?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($exam->published_at)
                                <x-badge variant="success">প্রকাশিত</x-badge>
                            @else
                                <x-badge variant="warning">অপ্রকাশিত</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('enter-marks')
                                @unless ($exam->published_at)
                                    <a href="{{ route('exams.marks.edit', $exam) }}" class="text-emerald-700 hover:underline text-sm">নম্বর এন্ট্রি / Marks</a>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
