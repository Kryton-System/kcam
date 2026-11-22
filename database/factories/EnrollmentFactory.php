<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Section;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'class_id' => SchoolClass::factory(),
            'section_id' => Section::factory(),
            'roll_no' => fake()->numberBetween(1, 50),
            'status' => 'active',
        ];
    }
}
