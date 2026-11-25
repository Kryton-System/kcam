<x-app-layout>
    <x-slot:title>
        {{ __('dashboard.title') }} | {{ $school?->name_bn ?? $school?->name ?? __('dashboard.fallback_school_name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $school?->name_bn ?? $school?->name }}</p>
        </div>
        <div class="text-sm text-slate-500">{{ \App\Support\Helpers\LocaleFormat::date(now()) }}</div>
    </x-slot:header>

    @include('partials.dashboard-quick-actions', ['actions' => [
        ['route' => 'fees.index', 'label' => __('dashboard.quick.fees'), 'show' => config('kcam.modules.fees')],
        ['route' => 'accounts.index', 'label' => __('dashboard.accountant.open_accounts'), 'show' => config('kcam.modules.accounts')],
    ]])

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-dashboard-figure :label="__('dashboard.accountant.collected_today')" :value="\App\Support\Helpers\LocaleFormat::taka($collectedToday)" :note="__('dashboard.accountant.payments_today', ['count' => \App\Support\Helpers\LocaleFormat::number($paymentsToday)])" tone="success" />
        <x-dashboard-figure :label="__('dashboard.accountant.collected_month')" :value="\App\Support\Helpers\LocaleFormat::taka($collectedThisMonth)" :note="__('dashboard.accountant.collected_month_note')" tone="success" />
        <x-dashboard-figure :label="__('dashboard.accountant.dues')" :value="\App\Support\Helpers\LocaleFormat::taka($outstandingDues)" :note="__('dashboard.accountant.dues_note')" tone="warning" />
        <x-dashboard-figure :label="__('dashboard.accountant.expenses_month')" :value="\App\Support\Helpers\LocaleFormat::taka($expensesThisMonth)" :note="__('dashboard.accountant.expenses_month_note')" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card :title="__('dashboard.accountant.recent_payments')">
            @if ($recentPayments->isEmpty())
                <x-empty-state :title="__('dashboard.payments.empty_title')" :description="__('dashboard.payments.empty_description')" />
            @else
                <x-table :headers="[__('dashboard.payments.student'), __('dashboard.payments.amount'), __('dashboard.payments.time')]">
                    @foreach ($recentPayments as $payment)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->invoice?->student?->name ?? __('dashboard.na') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::taka($payment->amount) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ \App\Support\Helpers\LocaleFormat::date($payment->paid_at) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </x-card>

        <x-card :title="__('dashboard.accountant.recent_receipts')">
            @if ($recentReceipts->isEmpty())
                <x-empty-state :title="__('dashboard.accountant.no_receipts')" />
            @else
                <x-table :headers="[__('dashboard.accountant.receipt_no'), __('dashboard.payments.student'), __('dashboard.payments.amount')]">
                    @foreach ($recentReceipts as $receipt)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-700">{{ $receipt->serial_no }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $receipt->payment?->invoice?->student?->name ?? __('dashboard.na') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::taka($receipt->payment?->amount ?? 0) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </x-card>

        <x-card :title="__('dashboard.accountant.recent_expenses')" class="lg:col-span-2">
            @if ($recentExpenses->isEmpty())
                <x-empty-state :title="__('dashboard.accountant.no_expenses')" />
            @else
                <x-table :headers="[__('dashboard.accountant.expense_date'), __('dashboard.accountant.category'), __('dashboard.accountant.payee'), __('dashboard.payments.amount')]">
                    @foreach ($recentExpenses as $expense)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ \App\Support\Helpers\LocaleFormat::date($expense->spent_on) }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $expense->category?->label() ?? __('dashboard.na') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $expense->payee ?: __('dashboard.na') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::taka($expense->amount) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </x-card>
    </div>
</x-app-layout>
