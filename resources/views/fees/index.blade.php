@php
    use App\Support\Enums\PaymentMethod;
    use App\Support\Helpers\BanglaHelper;
@endphp

<x-app-layout>
    <x-slot:title>
        ফি আদায় | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">ফি ও বকেয়া</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">বকেয়া চালান দেখুন ও ফি আদায় করুন</p>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('fees.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-input name="search" placeholder="নাম অথবা আইডি দিয়ে খুঁজুন..." :value="request('search')" />

            <x-select name="status" :selected="$status">
                <option value="unpaid" @selected($status === 'unpaid')>বকেয়া</option>
                <option value="paid" @selected($status === 'paid')>পরিশোধিত</option>
                <option value="all" @selected($status === 'all')>সব</option>
            </x-select>

            <x-button type="submit" variant="secondary">ফিল্টার করুন</x-button>
        </form>
    </x-card>

    <x-card class="mb-6">
        @if ($invoices->isEmpty())
            <x-empty-state title="কোনো চালান পাওয়া যায়নি" description="ফিল্টার বদলে আবার চেষ্টা করুন।" />
        @else
            <x-table :headers="['শিক্ষার্থী', 'মাস', 'মোট', 'পরিশোধিত', 'বকেয়া', 'আদায়']">
                @foreach ($invoices as $invoice)
                    <tr class="align-top">
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div>{{ $invoice->student->name_bn ?? $invoice->student->name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ $invoice->student->student_code }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $invoice->month }}</td>
                        <td class="px-4 py-3">{{ BanglaHelper::formatTaka($invoice->total, true) }}</td>
                        <td class="px-4 py-3">{{ BanglaHelper::formatTaka($invoice->paid, true) }}</td>
                        <td class="px-4 py-3 font-semibold text-red-700">{{ BanglaHelper::formatTaka($invoice->due, true) }}</td>
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
                                        aria-label="পরিমাণ"
                                    >
                                    <select name="method" class="rounded-lg border border-slate-300 px-2 py-2 text-sm" aria-label="মাধ্যম">
                                        @foreach (PaymentMethod::cases() as $method)
                                            <option value="{{ $method->value }}">{{ $method->labelBn() }}</option>
                                        @endforeach
                                    </select>
                                    <x-button type="submit" variant="primary" size="sm">আদায়</x-button>
                                </form>
                            @else
                                <x-badge variant="success">পরিশোধিত</x-badge>
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

    <x-card title="সাম্প্রতিক রসিদ">
        @forelse ($recentReceipts as $receipt)
            <div class="py-2 flex justify-between text-sm border-b border-slate-100 last:border-0">
                <span class="font-mono">{{ $receipt->serial_no }}</span>
                <span>{{ $receipt->payment->invoice->student->name_bn ?? $receipt->payment->invoice->student->name }}</span>
                <span class="font-semibold">{{ BanglaHelper::formatTaka($receipt->payment->amount, true) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">এখনও কোনো রসিদ নেই।</p>
        @endforelse
    </x-card>
</x-app-layout>
