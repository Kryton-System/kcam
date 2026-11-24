<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->words(2, true),
            'unit' => 'pcs',
            'low_stock_threshold' => 5,
        ];
    }
}
