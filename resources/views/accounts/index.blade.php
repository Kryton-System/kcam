@php
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('accounts.index.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('accounts.index.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('accounts.index.subheading') }}</p>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('accounts.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <x-input name="month" type="month" :label="__('accounts.common.month')" :value="$month->format('Y-m')" />
            <x-button type="submit" variant="secondary">{{ __('accounts.common.show') }}</x-button>
            <div class="flex gap-2">
                @can('view-accounts')
                    <x-button :href="route('accounts.expenses', ['month' => $month->format('Y-m')])" variant="outline">{{ __('accounts.index.expenses_link') }}</x-button>
                    <x-button :href="route('accounts.cash-book')" variant="outline">{{ __('accounts.index.cash_book_link') }}</x-button>
                @endcan
            </div>
        </form>
    </x-card>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card :title="__('accounts.index.fee_income')">
            <p class="text-2xl font-bold text-emerald-700">{{ \App\Support\Helpers\LocaleFormat::taka($summary['income'], true) }}</p>
        </x-card>
        <x-card :title="__('accounts.common.expenses')">
            <p class="text-2xl font-bold text-red-700">{{ \App\Support\Helpers\LocaleFormat::taka($summary['expenses'], true) }}</p>
        </x-card>
        <x-card :title="__('accounts.index.net')">
            <p class="text-2xl font-bold {{ $summary['net'] < 0 ? 'text-red-700' : 'text-slate-900' }}">{{ \App\Support\Helpers\LocaleFormat::taka($summary['net'], true) }}</p>
        </x-card>
    </div>

    <x-card :title="__('accounts.index.by_category')">
        @if (count($summary['categories']) === 0)
            <x-empty-state :title="__('accounts.index.empty_title')" description="" />
        @else
            <x-table :headers="[__('accounts.index.col_category'), __('accounts.index.col_total')]">
                @foreach ($summary['categories'] as $row)
                    <tr>
                        <td class="px-4 py-3 text-slate-800">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 font-semibold">{{ \App\Support\Helpers\LocaleFormat::taka($row['total'], true) }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-layout>
