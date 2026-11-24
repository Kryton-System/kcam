@php
    use App\Support\Helpers\BanglaHelper;
@endphp

<x-app-layout>
    <x-slot:title>
        হিসাব | Accounts | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">হিসাব / Accounts</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">মাসিক আয়-ব্যয় / Monthly income and expense</p>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('accounts.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <x-input name="month" type="month" label="মাস / Month" :value="$month->format('Y-m')" />
            <x-button type="submit" variant="secondary">দেখুন / Show</x-button>
            <div class="flex gap-2">
                @can('view-accounts')
                    <x-button :href="route('accounts.expenses', ['month' => $month->format('Y-m')])" variant="outline">খরচ / Expenses</x-button>
                    <x-button :href="route('accounts.cash-book')" variant="outline">ক্যাশ বুক / Cash book</x-button>
                @endcan
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card title="ফি আদায় / Fee income">
            <p class="text-2xl font-bold text-emerald-700">{{ BanglaHelper::formatTaka($summary['income'], true) }}</p>
        </x-card>
        <x-card title="খরচ / Expenses">
            <p class="text-2xl font-bold text-red-700">{{ BanglaHelper::formatTaka($summary['expenses'], true) }}</p>
        </x-card>
        <x-card title="নিট / Net">
            <p class="text-2xl font-bold {{ $summary['net'] < 0 ? 'text-red-700' : 'text-slate-900' }}">{{ BanglaHelper::formatTaka($summary['net'], true) }}</p>
        </x-card>
    </div>

    <x-card title="খাতভিত্তিক খরচ / Expense by category">
        @if (count($summary['categories']) === 0)
            <x-empty-state title="এই মাসে কোনো খরচ নেই / No expenses this month" description="" />
        @else
            <x-table :headers="['খাত / Category', 'মোট / Total']">
                @foreach ($summary['categories'] as $row)
                    <tr>
                        <td class="px-4 py-3 text-slate-800">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 font-semibold">{{ BanglaHelper::formatTaka($row['total'], true) }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
