<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'unit',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'integer',
        ];
    }

    protected static function newFactory(): InventoryItemFactory
    {
        return InventoryItemFactory::new();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Current stock: always the sum of the ledger, never a stored column.
     */
    public function currentStock(): int
    {
        return (int) $this->movements()->sum('quantity');
    }

    public function isLowStock(int $stock): bool
    {
        return $stock <= $this->low_stock_threshold;
    }
}
