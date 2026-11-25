@php
    $canManage = auth()->user()->can('manage-expenses');
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('accounts.expenses.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('accounts.expenses.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('accounts.expenses.subheading') }}</p>
        </div>
    </x-slot:header>

    @if ($canManage)
        <x-card :title="__('accounts.expenses.new_expense')" class="mb-6">
            <form method="POST" action="{{ route('accounts.expenses.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-select name="expense_category_id" :label="__('accounts.common.category')" :placeholder="__('accounts.expenses.select')" required :selected="old('expense_category_id')">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id') === (string) $category->id)>{{ $category->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="spent_on" type="date" :label="__('accounts.common.date')" :value="old('spent_on', now()->toDateString())" required />
                    <x-input name="amount" :label="__('accounts.expenses.amount_taka')" inputmode="decimal" :value="old('amount')" required />
                    <x-input name="payee" :label="__('accounts.common.payee')" :value="old('payee')" required />
                </div>
                <x-input name="note" :label="__('accounts.expenses.note')" :value="old('note')" />
                <x-button type="submit" variant="primary">{{ __('accounts.expenses.save') }}</x-button>
            </form>
        </x-card>

        <x-card :title="__('accounts.expenses.new_category')" class="mb-6">
            <form method="POST" action="{{ route('accounts.categories.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <x-input name="name" :label="__('accounts.expenses.name_en')" :value="old('name')" required />
                <x-input name="name_bn" :label="__('accounts.expenses.name_bn')" :value="old('name_bn')" />
                <x-button type="submit" variant="secondary">{{ __('accounts.expenses.add') }}</x-button>
            </form>
        </x-card>
    @endif

    <x-card class="mb-6">
        <form method="GET" action="{{ route('accounts.expenses') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <x-input name="month" type="month" :label="__('accounts.common.month')" :value="$month->format('Y-m')" />
            <x-button type="submit" variant="secondary">{{ __('accounts.common.show') }}</x-button>
        </form>
    </x-card>

    <x-card>
        @if ($expenses->isEmpty())
            <x-empty-state :title="__('accounts.expenses.empty_title')" description="" />
        @else
            <x-table :headers="[__('accounts.common.date'), __('accounts.common.category'), __('accounts.common.payee'), __('accounts.common.amount'), __('accounts.expenses.col_status')]">
                @foreach ($expenses as $expense)
                    <tr class="align-top {{ $expense->isVoided() ? 'text-slate-400' : '' }}">
                        <td class="px-4 py-3">{{ $expense->spent_on->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $expense->category->label() }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $expense->payee }}</div>
                            @if ($expense->note)
                                <div class="text-[11px] text-slate-500">{{ $expense->note }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 {{ $expense->isVoided() ? 'line-through' : 'font-semibold' }}">{{ \App\Support\Helpers\LocaleFormat::taka($expense->amount, true) }}</td>
                        <td class="px-4 py-3">
                            @if ($expense->isVoided())
                                <span class="text-xs">{{ __('accounts.expenses.voided') }}: {{ $expense->void_reason }}</span>
                            @elseif ($canManage)
                                <form method="POST" action="{{ route('accounts.expenses.void', $expense) }}" class="flex gap-2 items-center">
                                    @csrf
                                    <input type="text" name="reason" required minlength="3" maxlength="255" placeholder="{{ __('accounts.expenses.reason') }}" aria-label="{{ __('accounts.expenses.reason') }}" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                    <x-button type="submit" variant="danger" size="sm">{{ __('accounts.expenses.void') }}</x-button>
                                </form>
                            @else
                                <span class="text-xs">{{ __('accounts.expenses.active') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <div class="mt-4">{{ $expenses->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
