<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'type' => StockMovementType::In,
            'quantity' => 10,
            'reason' => 'Purchase',
            'note' => null,
            'user_id' => User::factory(),
        ];
    }
}
