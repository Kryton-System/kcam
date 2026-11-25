<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Inventory\Actions\InsufficientStockException;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\InventoryItem;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\StoreStockMovementRequest;
use App\Support\Enums\StockMovementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $items = InventoryItem::query()
            ->withSum('movements as stock', 'quantity')
            ->orderBy('name')
            ->paginate(20);

        return view('inventory.index', ['items' => $items]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $item = InventoryItem::create([
            'school_id' => $request->user()->school_id,
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'low_stock_threshold' => (int) $validated['low_stock_threshold'],
        ]);

        activity()->causedBy($request->user())->performedOn($item)->log('inventory_item_created');

        return redirect()->route('inventory.index')->with('success', __('inventory.flash.item_added'));
    }

    public function show(InventoryItem $item): View
    {
        return view('inventory.show', [
            'item' => $item,
            'stock' => $item->currentStock(),
            'movements' => $item->movements()->with('user')->latest('id')->paginate(25),
            'types' => StockMovementType::cases(),
        ]);
    }

    public function storeMovement(
        StoreStockMovementRequest $request,
        InventoryItem $item,
        RecordStockMovementAction $record,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $record->execute(
                $item,
                StockMovementType::from($validated['type']),
                (int) $validated['quantity'],
                $validated['reason'],
                $validated['note'] ?? null,
                $request->user(),
            );
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'quantity' => __('inventory.flash.insufficient_stock', ['detail' => $e->getMessage()]),
            ]);
        }

        return redirect()->route('inventory.show', $item)->with('success', __('inventory.flash.movement_recorded'));
    }
}
