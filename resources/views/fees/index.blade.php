@php
    use App\Support\Enums\PaymentMethod;
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('fees.index.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('fees.index.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('fees.index.subheading') }}</p>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('fees.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-input name="search" :placeholder="__('fees.index.search_placeholder')" :value="request('search')" />

            <x-select name="status" :selected="$status">
                <option value="unpaid" @selected($status === 'unpaid')>{{ __('fees.index.status_unpaid') }}</option>
                <option value="paid" @selected($status === 'paid')>{{ __('fees.index.status_paid') }}</option>
                <option value="all" @selected($status === 'all')>{{ __('fees.index.status_all') }}</option>
            </x-select>

            <x-button type="submit" variant="secondary">{{ __('fees.index.filter') }}</x-button>
        </form>
    </x-card>

    <x-card class="mb-6">
        @if ($invoices->isEmpty())
            <x-empty-state :title="__('fees.index.empty_title')" :description="__('fees.index.empty_description')" />
        @else
            <x-table :headers="[__('fees.index.col_student'), __('fees.index.col_month'), __('fees.index.col_total'), __('fees.index.col_paid'), __('fees.index.col_due'), __('fees.index.col_collect')]">
                @foreach ($invoices as $invoice)
                    <tr class="align-top">
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div>{{ $invoice->student->name_bn ?? $invoice->student->name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ $invoice->student->student_code }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $invoice->month }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Helpers\LocaleFormat::taka($invoice->total, true) }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Helpers\LocaleFormat::taka($invoice->paid, true) }}</td>
                        <td class="px-4 py-3 font-semibold text-red-700">{{ \App\Support\Helpers\LocaleFormat::taka($invoice->due, true) }}</td>
                        <td class="px-4 py-3">
                            @if ($invoice->due > 0 && $invoice->status !== 'cancelled')
                                <form method="POST" action="{{ route('fees.collect', $invoice) }}" class="flex flex-wrap gap-2 items-center">
                                    @csrf
                                    <input type="hidden" name="client_uuid" value="{{ \Illuminate\Support\Str::uuid() }}">
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        name="amount"
                                        value="{{ \App\Support\Money::toTaka($invoice->due) }}"
                                        class="w-28 rounded-lg border border-slate-300 px-2 py-2 text-sm"
                                        aria-label="{{ __('fees.index.amount') }}"
                                    >
                                    <select name="method" class="rounded-lg border border-slate-300 px-2 py-2 text-sm" aria-label="{{ __('fees.index.method') }}">
                                        @foreach (PaymentMethod::cases() as $method)
                                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-button type="submit" variant="primary" size="sm">{{ __('fees.index.collect') }}</x-button>
                                </form>
                            @else
                                <x-badge variant="success">{{ __('fees.index.paid_badge') }}</x-badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @error('amount')
                <p class="text-sm text-red-600 mt-3">{{ $message }}</p>
            @enderror
            @error('method')
                <p class="text-sm text-red-600 mt-3">{{ $message }}</p>
            @enderror

            <div class="mt-4">{{ $invoices->links() }}</div>
        @endif
    </x-card>

    <x-card :title="__('fees.index.recent_receipts')">
        @forelse ($recentReceipts as $receipt)
            <div class="py-2 flex justify-between text-sm border-b border-slate-100 last:border-0">
                <span class="font-mono">{{ $receipt->serial_no }}</span>
                <span>{{ $receipt->payment->invoice->student->name_bn ?? $receipt->payment->invoice->student->name }}</span>
                <span class="font-semibold">{{ \App\Support\Helpers\LocaleFormat::taka($receipt->payment->amount, true) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('fees.index.no_receipts') }}</p>
        @endforelse
    </x-card>
</x-app-layout>
