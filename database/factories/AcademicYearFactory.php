<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => '2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ];
    }
}
