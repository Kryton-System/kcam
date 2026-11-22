<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\Mark;
use App\Domain\School\Models\School;
use App\Domain\School\Models\Subject;
use App\Domain\Student\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarkFactory extends Factory
{
    protected $model = Mark::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'exam_id' => Exam::factory(),
            'enrollment_id' => Enrollment::factory(),
            'subject_id' => Subject::factory(),
            'marks' => '85.00',
            'grade' => 'A+',
            'gp' => '5.00',
        ];
    }
}
