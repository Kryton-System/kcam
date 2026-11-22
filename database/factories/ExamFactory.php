<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Exams\Models\Exam;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name' => '1st Term Examination',
            'name_bn' => '১ম সাময়িক পরীক্ষা',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-10',
            'published_at' => null,
        ];
    }
}
