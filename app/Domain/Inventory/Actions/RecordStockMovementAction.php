<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Models\User;
use App\Support\Enums\StockMovementType;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordStockMovementAction
{
    /**
     * Append one line to the stock ledger.
     *
     * $quantity is positive for in and out; adjustments are signed. The item row is locked
     * for the length of the transaction so two concurrent removals cannot both pass the
     * "stock stays at or above zero" check.
     *
     * @throws InvalidArgumentException
     * @throws InsufficientStockException
     */
    public function execute(
        InventoryItem $item,
        StockMovementType $type,
        int $quantity,
        string $reason,
        ?string $note = null,
        ?User $user = null,
    ): StockMovement {
        if ($quantity === 0 || ($type !== StockMovementType::Adjustment && $quantity < 0)) {
            throw new InvalidArgumentException('Quantity must be a non-zero whole number, positive for in and out.');
        }

        $delta = $type === StockMovementType::Out ? -$quantity : $quantity;

        return CurrentSchool::runAs(
            (int) $item->school_id,
            fn (): StockMovement => $this->append($item, $type, $delta, $reason, $note, $user),
        );
    }

    private function append(
        InventoryItem $item,
        StockMovementType $type,
        int $delta,
        string $reason,
        ?string $note,
        ?User $user,
    ): StockMovement {
        return DB::transaction(function () use ($item, $type, $delta, $reason, $note, $user): StockMovement {
            $locked = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            $available = $locked->currentStock();

            if ($available + $delta < 0) {
                throw InsufficientStockException::for($available, abs($delta));
            }

            return StockMovement::create([
                'school_id' => $locked->school_id,
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => $delta,
                'reason' => $reason,
                'note' => $note,
                'user_id' => $user?->id,
            ]);
        });
    }
}
