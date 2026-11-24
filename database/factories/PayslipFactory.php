<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Models\Payslip;
use App\Domain\School\Models\School;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'staff_id' => fn (array $attributes) => Staff::factory()->create(['school_id' => $attributes['school_id'], 'user_id' => null])->id,
            'month' => '2026-01',
            'basic' => 1500000,
            'deductions' => 0,
            'advances' => 0,
            'net' => 1500000,
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['status' => 'paid', 'paid_at' => now()]);
    }
}
