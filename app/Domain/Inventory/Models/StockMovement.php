<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Models\User;
use App\Support\Enums\StockMovementType;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line of the append-only stock ledger. quantity is a signed delta: positive adds
 * stock, negative removes it. Rows are never updated or deleted; fix mistakes with an
 * adjustment.
 */
class StockMovement extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'type',
        'quantity',
        'reason',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Stock movements are append-only and cannot be edited.');
        });

        static::deleting(function (): never {
            throw new LogicException('Stock movements are append-only and cannot be deleted.');
        });
    }

    protected static function newFactory(): StockMovementFactory
    {
        return StockMovementFactory::new();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
