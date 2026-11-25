<x-app-layout>
    <x-slot:title>
        {{ __('inventory.title') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('inventory.title') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('inventory.index.subtitle') }}</p>
        </div>
    </x-slot:header>

    <x-card :title="__('inventory.index.new_item')" class="mb-6">
        <form method="POST" action="{{ route('inventory.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-input name="name" :label="__('inventory.index.name')" :value="old('name')" required />
                <x-input name="unit" :label="__('inventory.index.unit')" :value="old('unit')" :placeholder="__('inventory.index.unit_placeholder')" required />
                <x-input name="low_stock_threshold" :label="__('inventory.index.low_stock_level')" type="text" inputmode="numeric" :value="old('low_stock_threshold', 0)" required />
            </div>

            <x-button type="submit" variant="primary">{{ __('inventory.index.add') }}</x-button>
        </form>
    </x-card>

    <x-card>
        @if ($items->isEmpty())
            <x-empty-state :title="__('inventory.index.empty_title')" :description="__('inventory.index.empty_description')" />
        @else
            <x-table :headers="[__('inventory.index.name'), __('inventory.index.unit'), __('inventory.index.col_stock'), __('inventory.index.col_status'), '']">
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
                                <x-badge variant="danger" data-low-stock>{{ __('inventory.index.low_stock') }}</x-badge>
                            @else
                                <x-badge variant="success">{{ __('inventory.index.ok') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('inventory.show', $item) }}" class="text-emerald-700 font-medium hover:underline">{{ __('inventory.index.ledger') }}</a>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
