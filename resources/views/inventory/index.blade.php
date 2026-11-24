<x-app-layout>
    <x-slot:title>
        ইনভেন্টরি / Inventory
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">ইনভেন্টরি / Inventory</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">স্কুলের মালামালের স্টক / School supplies stock</p>
        </div>
    </x-slot:header>

    <x-card title="নতুন আইটেম / New item" class="mb-6">
        <form method="POST" action="{{ route('inventory.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-input name="name" label="নাম / Name" :value="old('name')" required />
                <x-input name="unit" label="একক / Unit" :value="old('unit')" placeholder="pcs, kg, box" required />
                <x-input name="low_stock_threshold" label="সতর্কতার সীমা / Low stock level" type="text" inputmode="numeric" :value="old('low_stock_threshold', 0)" required />
            </div>

            <x-button type="submit" variant="primary">যোগ করুন / Add</x-button>
        </form>
    </x-card>

    <x-card>
        @if ($items->isEmpty())
            <x-empty-state title="কোনো আইটেম নেই / No items yet" description="প্রথম আইটেম যোগ করুন / Add your first item above." />
        @else
            <x-table :headers="['নাম / Name', 'একক / Unit', 'স্টক / Stock', 'অবস্থা / Status', '']">
                @foreach ($items as $item)
                    @php
                        $stock = (int) $item->stock;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $item->unit }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $stock }}</td>
                        <td class="px-4 py-3">
                            @if ($item->isLowStock($stock))
                                <x-badge variant="danger" data-low-stock>কম স্টক / Low stock</x-badge>
                            @else
                                <x-badge variant="success">পর্যাপ্ত / OK</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('inventory.show', $item) }}" class="text-emerald-700 font-medium hover:underline">খাতা / Ledger</a>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
