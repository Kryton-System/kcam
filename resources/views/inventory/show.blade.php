<x-app-layout>
    <x-slot:title>
        {{ $item->name }} | {{ __('inventory.title') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <a href="{{ route('inventory.index') }}" class="text-xs text-emerald-700 hover:underline">&larr; {{ __('inventory.title') }}</a>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight mt-1">{{ $item->name }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                {{ __('inventory.show.current_stock') }}: <span class="font-semibold text-slate-900">{{ $stock }} {{ $item->unit }}</span>
                @if ($item->isLowStock($stock))
                    <x-badge variant="danger" data-low-stock>{{ __('inventory.index.low_stock') }}</x-badge>
                @endif
            </p>
        </div>
    </x-slot:header>

    <x-card :title="__('inventory.show.new_movement')" class="mb-6">
        <form method="POST" action="{{ route('inventory.movements.store', $item) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select name="type" :label="__('inventory.show.type')" required :selected="old('type', 'in')">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', 'in') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </x-select>
                <x-input name="quantity" :label="__('inventory.show.quantity')" type="text" inputmode="numeric" :value="old('quantity')" :hint="__('inventory.show.quantity_hint')" required />
                <x-input name="reason" :label="__('inventory.show.reason')" :value="old('reason')" required />
                <x-input name="note" :label="__('inventory.show.note')" :value="old('note')" />
            </div>

            <x-button type="submit" variant="primary">{{ __('inventory.show.save') }}</x-button>
        </form>
    </x-card>

    <x-card :title="__('inventory.show.ledger_title')">
        @if ($movements->isEmpty())
            <x-empty-state :title="__('inventory.show.empty_title')" :description="__('inventory.show.empty_description')" />
        @else
            <x-table :headers="[__('inventory.show.col_date'), __('inventory.show.col_type'), __('inventory.show.col_qty'), __('inventory.show.col_reason'), __('inventory.show.col_note'), __('inventory.show.col_user')]">
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
