<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounts\Models\Expense;
use App\Domain\Accounts\Models\ExpenseCategory;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'expense_category_id' => fn (array $attrs) => ExpenseCategory::factory()->create(['school_id' => $attrs['school_id']])->id,
            'spent_on' => now()->toDateString(),
            'amount' => 50000,
            'payee' => 'Local Stationery',
            'note' => null,
            'recorded_by' => null,
        ];
    }
}
