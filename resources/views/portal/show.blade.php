@php
    use App\Support\Enums\AttendanceStatus;
@endphp
<x-app-layout>
    <x-slot:title>{{ $student->name }}</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ $student->name_bn ?? $student->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ $student->name }} | {{ $student->student_code }}</p>
        </div>
        <x-button :href="route('portal.index')" variant="secondary">← {{ __('portal.my_children') }}</x-button>
    </x-slot:header>

    @if ($siblings->count() > 1)
        <nav data-child-switcher class="flex flex-wrap gap-2 mb-6" aria-label="{{ __('portal.show.switch_child') }}">
            @foreach ($siblings as $sibling)
                <a
                    href="{{ route('portal.children.show', $sibling) }}"
                    @if ($sibling->is($student)) aria-current="page" @endif
                    class="inline-flex items-center min-h-[44px] rounded-lg border px-4 text-sm font-medium {{ $sibling->is($student) ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-800 hover:border-emerald-500' }}"
                >{{ $sibling->name_bn ?? $sibling->name }}</a>
            @endforeach
        </nav>
    @endif

    <div class="max-w-3xl space-y-6">
        <x-card :title="__('portal.show.profile')">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm p-5">
                @foreach ([
                    __('portal.show.class') => $enrollment?->schoolClass?->name_bn ?? $enrollment?->schoolClass?->name,
                    __('portal.show.section') => $enrollment?->section?->name,
                    __('portal.show.roll') => $enrollment?->roll_no,
                    __('portal.show.dob') => $student->dob?->format('d/m/Y'),
                    __('portal.show.blood_group') => $student->blood_group,
                ] as $label => $value)
                    <div>
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <x-card :title="__('portal.show.attendance')">
            <dl class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm p-5">
                @foreach (AttendanceStatus::cases() as $status)
                    <div>
                        <dt class="text-slate-500">{{ $status->label() }}</dt>
                        <dd class="font-bold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::digits($attendance[$status->value]) }}</dd>
                    </div>
                @endforeach
                <div>
                    <dt class="text-slate-500">{{ __('portal.show.total') }}</dt>
                    <dd class="font-bold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::digits($attendance['total']) }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card :title="__('portal.show.dues')">
            <div class="p-5 space-y-3 text-sm">
                <p class="font-semibold">{{ __('portal.show.total_due') }}: {{ \App\Support\Helpers\LocaleFormat::taka($totalDue, true) }}</p>
                @forelse ($invoices as $invoice)
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span>{{ $invoice->month }} | {{ $invoice->status }}</span>
                        <span>{{ \App\Support\Helpers\LocaleFormat::taka($invoice->paid, true) }} / {{ \App\Support\Helpers\LocaleFormat::taka($invoice->total, true) }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('portal.show.no_invoices') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card :title="__('portal.show.receipts')">
            <div class="p-5 space-y-2 text-sm">
                @forelse ($receipts as $receipt)
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span>{{ $receipt->serial_no }}</span>
                        <span>{{ \App\Support\Helpers\LocaleFormat::taka((int) $receipt->payment?->amount, true) }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('portal.show.no_receipts') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card :title="__('portal.show.results')">
            <div class="p-5 space-y-4 text-sm">
                @forelse ($results as $marks)
                    <div>
                        <h3 class="font-semibold">{{ $marks->first()->exam->name_bn ?? $marks->first()->exam->name }}</h3>
                        @foreach ($marks as $mark)
                            <div class="flex justify-between border-b border-slate-100 py-1">
                                <span>{{ $mark->subject?->name_bn ?? $mark->subject?->name }}</span>
                                <span>{{ $mark->marks }} | {{ $mark->grade }} | {{ $mark->gp }}</span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('portal.show.no_results') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card :title="__('portal.show.notices')">
            <div class="p-5 space-y-3 text-sm">
                @forelse ($notices as $notice)
                    <div class="border-b border-slate-100 pb-2">
                        <h3 class="font-semibold">{{ $notice->title_bn ?? $notice->title }}</h3>
                        <p class="text-slate-600">{{ $notice->body_bn ?? $notice->body }}</p>
                    </div>
                @empty
                    <p class="text-slate-500">{{ __('portal.show.no_notices') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
