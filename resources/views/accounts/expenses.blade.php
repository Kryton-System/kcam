@php
    use App\Support\Helpers\BanglaHelper;
    $canManage = auth()->user()->can('manage-expenses');
@endphp

<x-app-layout>
    <x-slot:title>
        খরচ | Expenses | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">খরচ / Expenses</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">খরচ যোগ করুন, বাতিল হলে কারণ লিখুন / Record expenses; voids need a reason</p>
        </div>
    </x-slot:header>

    @if ($canManage)
        <x-card title="নতুন খরচ / New expense" class="mb-6">
            <form method="POST" action="{{ route('accounts.expenses.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-select name="expense_category_id" label="খাত / Category" placeholder="নির্বাচন করুন / Select" required :selected="old('expense_category_id')">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id') === (string) $category->id)>{{ $category->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="spent_on" type="date" label="তারিখ / Date" :value="old('spent_on', now()->toDateString())" required />
                    <x-input name="amount" label="পরিমাণ (টাকা) / Amount (taka)" inputmode="decimal" :value="old('amount')" required />
                    <x-input name="payee" label="প্রাপক / Payee" :value="old('payee')" required />
                </div>
                <x-input name="note" label="মন্তব্য / Note" :value="old('note')" />
                <x-button type="submit" variant="primary">সংরক্ষণ / Save</x-button>
            </form>
        </x-card>

        <x-card title="নতুন খাত / New category" class="mb-6">
            <form method="POST" action="{{ route('accounts.categories.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <x-input name="name" label="নাম (English) / Name" :value="old('name')" required />
                <x-input name="name_bn" label="নাম (বাংলা) / Bangla name" :value="old('name_bn')" />
                <x-button type="submit" variant="secondary">যোগ করুন / Add</x-button>
            </form>
        </x-card>
    @endif

    <x-card class="mb-6">
        <form method="GET" action="{{ route('accounts.expenses') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <x-input name="month" type="month" label="মাস / Month" :value="$month->format('Y-m')" />
            <x-button type="submit" variant="secondary">দেখুন / Show</x-button>
        </form>
    </x-card>

    <x-card>
        @if ($expenses->isEmpty())
            <x-empty-state title="কোনো খরচ নেই / No expenses" description="" />
        @else
            <x-table :headers="['তারিখ / Date', 'খাত / Category', 'প্রাপক / Payee', 'পরিমাণ / Amount', 'অবস্থা / Status']">
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
                        <td class="px-4 py-3 {{ $expense->isVoided() ? 'line-through' : 'font-semibold' }}">{{ BanglaHelper::formatTaka($expense->amount, true) }}</td>
                        <td class="px-4 py-3">
                            @if ($expense->isVoided())
                                <span class="text-xs">বাতিল / Voided: {{ $expense->void_reason }}</span>
                            @elseif ($canManage)
                                <form method="POST" action="{{ route('accounts.expenses.void', $expense) }}" class="flex gap-2 items-center">
                                    @csrf
                                    <input type="text" name="reason" required minlength="3" maxlength="255" placeholder="কারণ / Reason" aria-label="কারণ / Reason" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                    <x-button type="submit" variant="danger" size="sm">বাতিল / Void</x-button>
                                </form>
                            @else
                                <span class="text-xs">সক্রিয় / Active</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <div class="mt-4">{{ $expenses->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
