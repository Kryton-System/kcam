<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Fees\Models\FeeDiscount;
use App\Domain\Fees\Models\FeeHead;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeDiscountFactory extends Factory
{
    protected $model = FeeDiscount::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'fee_head_id' => FeeHead::factory(),
            'type' => 'fixed',
            'value' => 20000,
            'reason' => 'Sibling discount',
        ];
    }
}
