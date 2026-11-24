@php
    use App\Support\Helpers\BanglaHelper;
@endphp

<x-app-layout>
    <x-slot:title>
        বেতন স্লিপ / Payslip | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">বেতন স্লিপ / Payslip</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ $payslip->month }}</p>
        </div>

        <div class="flex gap-2 print:hidden">
            <x-button :href="route('payroll.index', ['month' => $payslip->month])" variant="secondary">← ফিরে যান / Back</x-button>
            <button type="button" onclick="window.print()" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 min-h-[48px]">প্রিন্ট / Print</button>
        </div>
    </x-slot:header>

    <style media="print">
        aside, nav, header, .print\:hidden { display: none !important; }
        body { padding: 0 !important; background: #fff !important; }
        main { margin: 0 !important; padding: 0 !important; }
    </style>

    <div class="max-w-2xl bg-white border border-slate-300 rounded-xl p-6">
        <div class="text-center border-b border-slate-300 pb-4 mb-4">
            <h2 class="text-lg font-bold text-slate-900">{{ $school?->name }}</h2>
            <p class="text-sm text-slate-600">বেতন স্লিপ / Payslip | {{ $payslip->month }}</p>
        </div>

        <dl class="grid grid-cols-2 gap-y-2 text-sm mb-4">
            <dt class="text-slate-500">নাম / Name</dt>
            <dd class="font-semibold">{{ $payslip->staff->name_bn ?: $payslip->staff->name }}</dd>
            <dt class="text-slate-500">পদবি / Designation</dt>
            <dd>{{ $payslip->staff->designation }}</dd>
            <dt class="text-slate-500">মোবাইল / Mobile</dt>
            <dd>{{ $payslip->staff->phone ?? '-' }}</dd>
        </dl>

        <table class="w-full text-sm border-t border-slate-300">
            <tbody class="divide-y divide-slate-200">
                <tr><td class="py-2">মূল বেতন / Basic salary</td><td class="py-2 text-right">{{ BanglaHelper::formatTaka($payslip->basic, true) }}</td></tr>
                <tr><td class="py-2">কর্তন / Deductions</td><td class="py-2 text-right">- {{ BanglaHelper::formatTaka($payslip->deductions, true) }}</td></tr>
                <tr><td class="py-2">অগ্রিম / Advances</td><td class="py-2 text-right">- {{ BanglaHelper::formatTaka($payslip->advances, true) }}</td></tr>
                <tr class="font-bold"><td class="py-2">নিট বেতন / Net pay</td><td class="py-2 text-right">{{ BanglaHelper::formatTaka($payslip->net, true) }}</td></tr>
            </tbody>
        </table>

        @if ($payslip->notes)
            <p class="text-sm text-slate-600 mt-4">মন্তব্য / Notes: {{ $payslip->notes }}</p>
        @endif

        <p class="text-sm mt-4">
            অবস্থা / Status:
            @if ($payslip->isPaid())
                পরিশোধিত / Paid ({{ $payslip->paid_at?->format('Y-m-d') }})
            @else
                বাকি / Pending
            @endif
        </p>

        <div class="grid grid-cols-2 gap-8 mt-12 text-xs text-slate-600">
            <div class="border-t border-slate-400 pt-1">প্রাপকের স্বাক্ষর / Receiver signature</div>
            <div class="border-t border-slate-400 pt-1 text-right">কর্তৃপক্ষের স্বাক্ষর / Authority signature</div>
        </div>
    </div>
</x-app-layout>
