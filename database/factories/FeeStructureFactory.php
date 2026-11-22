<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Fees\Models\FeeHead;
use App\Domain\Fees\Models\FeeStructure;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'class_id' => SchoolClass::factory(),
            'fee_head_id' => FeeHead::factory(),
            'amount' => fake()->randomElement([50000, 80000, 100000, 150000]),
        ];
    }
}
