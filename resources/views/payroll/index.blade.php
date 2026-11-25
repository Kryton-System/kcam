@php
    use App\Support\Money;
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('payroll.index.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('payroll.index.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('payroll.index.subheading') }}</p>
        </div>

        <div>
            <x-button :href="route('staff.index')" variant="secondary">{{ __('payroll.index.staff') }}</x-button>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <form method="GET" action="{{ route('payroll.index') }}" class="flex gap-2 items-end">
                <x-input name="month" type="month" :label="__('payroll.index.month')" :value="$month" />
                <x-button type="submit" variant="secondary">{{ __('payroll.index.view') }}</x-button>
            </form>

            <form method="POST" action="{{ route('payroll.generate') }}" class="flex gap-2 items-end sm:justify-end">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <x-button type="submit">{{ __('payroll.index.generate') }}</x-button>
            </form>
        </div>

        <p class="text-sm text-slate-600 mt-4">
            {{ __('payroll.index.total_net') }}: <strong>{{ \App\Support\Helpers\LocaleFormat::taka($totals['net'], true) }}</strong>
            | {{ __('payroll.index.paid_total') }}: <strong>{{ \App\Support\Helpers\LocaleFormat::taka($totals['paid'], true) }}</strong>
        </p>
    </x-card>

    <x-card class="mb-6">
        @if ($payslips->isEmpty())
            <x-empty-state :title="__('payroll.index.empty_title')" :description="__('payroll.index.empty_description')" />
        @else
            <x-table :headers="[__('payroll.index.staff'), __('payroll.index.col_basic'), __('payroll.index.col_deductions'), __('payroll.index.col_advances'), __('payroll.index.col_net'), __('payroll.index.col_status'), '']">
                @foreach ($payslips as $payslip)
                    <tr class="align-top">
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div>{{ $payslip->staff->name_bn ?: $payslip->staff->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $payslip->staff->designation }}</div>
                        </td>
                        <td class="px-4 py-3">{{ \App\Support\Helpers\LocaleFormat::taka($payslip->basic, true) }}</td>
                        @if ($payslip->isPaid())
                            <td class="px-4 py-3">{{ \App\Support\Helpers\LocaleFormat::taka($payslip->deductions, true) }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Helpers\LocaleFormat::taka($payslip->advances, true) }}</td>
                            <td class="px-4 py-3 font-semibold">{{ \App\Support\Helpers\LocaleFormat::taka($payslip->net, true) }}</td>
                            <td class="px-4 py-3 text-emerald-700 font-semibold">{{ __('payroll.index.paid') }}</td>
                            <td class="px-4 py-3"><a href="{{ route('payroll.show', $payslip) }}" class="text-emerald-700 font-semibold">{{ __('payroll.index.payslip') }}</a></td>
                        @else
                            <td class="px-4 py-3" colspan="2">
                                <form method="POST" action="{{ route('payroll.update', $payslip) }}" class="flex flex-wrap gap-2 items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" inputmode="decimal" name="deductions" value="{{ Money::toTaka($payslip->deductions) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-2 text-sm" aria-label="{{ __('payroll.index.col_deductions') }}">
                                    <input type="text" inputmode="decimal" name="advances" value="{{ Money::toTaka($payslip->advances) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-2 text-sm" aria-label="{{ __('payroll.index.col_advances') }}">
                                    <x-button type="submit" variant="secondary" size="sm">{{ __('payroll.index.update') }}</x-button>
                                </form>
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ \App\Support\Helpers\LocaleFormat::taka($payslip->net, true) }}</td>
                            <td class="px-4 py-3 text-amber-700 font-semibold">{{ __('payroll.index.pending') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <form method="POST" action="{{ route('payroll.pay', $payslip) }}">
                                        @csrf
                                        <x-button type="submit" size="sm">{{ __('payroll.index.mark_paid') }}</x-button>
                                    </form>
                                    <a href="{{ route('payroll.show', $payslip) }}" class="text-emerald-700 font-semibold">{{ __('payroll.index.payslip') }}</a>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
