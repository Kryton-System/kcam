@php
    use App\Support\Helpers\BanglaHelper;
    use App\Support\Money;
@endphp

<x-app-layout>
    <x-slot:title>
        বেতন / Payroll | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">বেতন / Payroll</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">মাসিক বেতন স্লিপ, কর্তন ও অগ্রিম / Monthly payslips, deductions and advances</p>
        </div>

        <div>
            <x-button :href="route('staff.index')" variant="secondary">কর্মী / Staff</x-button>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <form method="GET" action="{{ route('payroll.index') }}" class="flex gap-2 items-end">
                <x-input name="month" type="month" label="মাস / Month" :value="$month" />
                <x-button type="submit" variant="secondary">দেখুন / View</x-button>
            </form>

            <form method="POST" action="{{ route('payroll.generate') }}" class="flex gap-2 items-end sm:justify-end">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <x-button type="submit">এই মাসের স্লিপ তৈরি করুন / Generate payslips</x-button>
            </form>
        </div>

        <p class="text-sm text-slate-600 mt-4">
            মোট নিট বেতন / Total net: <strong>{{ BanglaHelper::formatTaka($totals['net'], true) }}</strong>
            | পরিশোধিত / Paid: <strong>{{ BanglaHelper::formatTaka($totals['paid'], true) }}</strong>
        </p>
    </x-card>

    <x-card class="mb-6">
        @if ($payslips->isEmpty())
            <x-empty-state title="এই মাসে কোনো স্লিপ নেই / No payslips this month" description="স্লিপ তৈরি করুন বাটনে চাপ দিন। / Press Generate payslips." />
        @else
            <x-table :headers="['কর্মী / Staff', 'মূল বেতন / Basic', 'কর্তন / Deductions', 'অগ্রিম / Advances', 'নিট / Net', 'অবস্থা / Status', '']">
                @foreach ($payslips as $payslip)
                    <tr class="align-top">
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div>{{ $payslip->staff->name_bn ?: $payslip->staff->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $payslip->staff->designation }}</div>
                        </td>
                        <td class="px-4 py-3">{{ BanglaHelper::formatTaka($payslip->basic, true) }}</td>
                        @if ($payslip->isPaid())
                            <td class="px-4 py-3">{{ BanglaHelper::formatTaka($payslip->deductions, true) }}</td>
                            <td class="px-4 py-3">{{ BanglaHelper::formatTaka($payslip->advances, true) }}</td>
                            <td class="px-4 py-3 font-semibold">{{ BanglaHelper::formatTaka($payslip->net, true) }}</td>
                            <td class="px-4 py-3 text-emerald-700 font-semibold">পরিশোধিত / Paid</td>
                            <td class="px-4 py-3"><a href="{{ route('payroll.show', $payslip) }}" class="text-emerald-700 font-semibold">স্লিপ / Payslip</a></td>
                        @else
                            <td class="px-4 py-3" colspan="2">
                                <form method="POST" action="{{ route('payroll.update', $payslip) }}" class="flex flex-wrap gap-2 items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" inputmode="decimal" name="deductions" value="{{ Money::toTaka($payslip->deductions) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-2 text-sm" aria-label="কর্তন / Deductions">
                                    <input type="text" inputmode="decimal" name="advances" value="{{ Money::toTaka($payslip->advances) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-2 text-sm" aria-label="অগ্রিম / Advances">
                                    <x-button type="submit" variant="secondary" size="sm">হিসাব / Update</x-button>
                                </form>
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ BanglaHelper::formatTaka($payslip->net, true) }}</td>
                            <td class="px-4 py-3 text-amber-700 font-semibold">বাকি / Pending</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <form method="POST" action="{{ route('payroll.pay', $payslip) }}">
                                        @csrf
                                        <x-button type="submit" size="sm">পরিশোধ / Mark paid</x-button>
                                    </form>
                                    <a href="{{ route('payroll.show', $payslip) }}" class="text-emerald-700 font-semibold">স্লিপ / Payslip</a>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
