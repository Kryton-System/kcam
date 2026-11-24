@php
    use App\Support\Enums\AttendanceStatus;
    use App\Support\Helpers\BanglaHelper;
@endphp
<x-app-layout>
    <x-slot:title>{{ $student->name }}</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ $student->name_bn ?? $student->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ $student->name }} | {{ $student->student_code }}</p>
        </div>
        <x-button :href="route('portal.index')" variant="secondary">← আমার সন্তান / My Children</x-button>
    </x-slot:header>

    <div class="max-w-3xl space-y-6">
        <x-card title="পরিচিতি / Profile">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm p-5">
                @foreach ([
                    'শ্রেণি / Class' => $enrollment?->schoolClass?->name_bn ?? $enrollment?->schoolClass?->name,
                    'শাখা / Section' => $enrollment?->section?->name,
                    'রোল / Roll' => $enrollment?->roll_no,
                    'জন্ম তারিখ / Date of birth' => $student->dob?->format('d/m/Y'),
                    'রক্তের গ্রুপ / Blood group' => $student->blood_group,
                ] as $label => $value)
                    <div>
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        <x-card title="হাজিরা / Attendance">
            <dl class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm p-5">
                @foreach (AttendanceStatus::cases() as $status)
                    <div>
                        <dt class="text-slate-500">{{ $status->labelBn() }} / {{ ucfirst($status->value) }}</dt>
                        <dd class="font-bold text-slate-900">{{ BanglaHelper::toBanglaDigits($attendance[$status->value]) }}</dd>
                    </div>
                @endforeach
                <div>
                    <dt class="text-slate-500">মোট / Total</dt>
                    <dd class="font-bold text-slate-900">{{ BanglaHelper::toBanglaDigits($attendance['total']) }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="বকেয়া ও ফি / Dues and Fees">
            <div class="p-5 space-y-3 text-sm">
                <p class="font-semibold">মোট বকেয়া / Total due: {{ BanglaHelper::formatTaka($totalDue, true) }}</p>
                @forelse ($invoices as $invoice)
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span>{{ $invoice->month }} | {{ $invoice->status }}</span>
                        <span>{{ BanglaHelper::formatTaka($invoice->paid, true) }} / {{ BanglaHelper::formatTaka($invoice->total, true) }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">কোনো চালান নেই / No invoices</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="রসিদ / Receipts">
            <div class="p-5 space-y-2 text-sm">
                @forelse ($receipts as $receipt)
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span>{{ $receipt->serial_no }}</span>
                        <span>{{ BanglaHelper::formatTaka((int) $receipt->payment?->amount, true) }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">কোনো রসিদ নেই / No receipts</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="ফলাফল / Results">
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
                    <p class="text-slate-500">কোনো প্রকাশিত ফলাফল নেই / No published results</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="বিজ্ঞপ্তি / Notices">
            <div class="p-5 space-y-3 text-sm">
                @forelse ($notices as $notice)
                    <div class="border-b border-slate-100 pb-2">
                        <h3 class="font-semibold">{{ $notice->title_bn ?? $notice->title }}</h3>
                        <p class="text-slate-600">{{ $notice->body_bn ?? $notice->body }}</p>
                    </div>
                @empty
                    <p class="text-slate-500">কোনো বিজ্ঞপ্তি নেই / No notices</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
