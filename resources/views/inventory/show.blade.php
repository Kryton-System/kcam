<x-app-layout>
    <x-slot:title>
        {{ $item->name }} | ইনভেন্টরি / Inventory
    </x-slot:title>

    <x-slot:header>
        <div>
            <a href="{{ route('inventory.index') }}" class="text-xs text-emerald-700 hover:underline">&larr; ইনভেন্টরি / Inventory</a>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight mt-1">{{ $item->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                বর্তমান স্টক / Current stock: <span class="font-semibold text-slate-900">{{ $stock }} {{ $item->unit }}</span>
                @if ($item->isLowStock($stock))
                    <x-badge variant="danger" data-low-stock>কম স্টক / Low stock</x-badge>
                @endif
            </p>
        </div>
    </x-slot:header>

    <x-card title="নতুন লেনদেন / New movement" class="mb-6">
        <form method="POST" action="{{ route('inventory.movements.store', $item) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select name="type" label="ধরন / Type" required :selected="old('type', 'in')">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', 'in') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </x-select>
                <x-input name="quantity" label="পরিমাণ / Quantity" type="text" inputmode="numeric" :value="old('quantity')" hint="সমন্বয়ে ঋণাত্মক সংখ্যা চলবে, যেমন -2 / Adjustments may be negative, e.g. -2" required />
                <x-input name="reason" label="কারণ / Reason" :value="old('reason')" required />
                <x-input name="note" label="মন্তব্য / Note" :value="old('note')" />
            </div>

            <x-button type="submit" variant="primary">সংরক্ষণ / Save</x-button>
        </form>
    </x-card>

    <x-card title="স্টক খাতা / Stock ledger">
        @if ($movements->isEmpty())
            <x-empty-state title="কোনো লেনদেন নেই / No movements yet" description="প্রথম লেনদেন যোগ করুন / Record the first movement above." />
        @else
            <x-table :headers="['তারিখ / Date', 'ধরন / Type', 'পরিমাণ / Qty', 'কারণ / Reason', 'মন্তব্য / Note', 'ব্যবহারকারী / User']">
                @foreach ($movements as $movement)
                    <tr>
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $movement->type->label() }}</td>
                        <td class="px-4 py-3 font-semibold {{ $movement->quantity < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $movement->reason }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $movement->note }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $movement->user?->name }}</td>
                    </tr>
                @endforeach
            </x-table>
            <div class="mt-4">{{ $movements->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
