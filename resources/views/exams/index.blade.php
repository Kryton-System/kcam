<x-app-layout>
    <x-slot:title>
        {{ __('exams.index.title') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('exams.index.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('exams.index.subheading') }}</p>
        </div>
    </x-slot:header>

    <x-card>
        @if ($exams->isEmpty())
            <x-empty-state :title="__('exams.index.empty_title')" :description="__('exams.index.empty_description')" />
        @else
            <x-table :headers="[__('exams.index.col_exam'), __('exams.index.col_start'), __('exams.index.col_end'), __('exams.index.col_result'), '']">
                @foreach ($exams as $exam)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $exam->name_bn ?? $exam->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $exam->starts_on?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $exam->ends_on?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($exam->published_at)
                                <x-badge variant="success">{{ __('exams.index.published') }}</x-badge>
                            @else
                                <x-badge variant="warning">{{ __('exams.index.unpublished') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('enter-marks')
                                @unless ($exam->published_at)
                                    <a href="{{ route('exams.marks.edit', $exam) }}" class="text-emerald-700 hover:underline text-sm">{{ __('exams.index.enter_marks') }}</a>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
