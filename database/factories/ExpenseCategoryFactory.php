<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounts\Models\ExpenseCategory;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->word(),
            'name_bn' => 'বিবিধ',
        ];
    }
}
