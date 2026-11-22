<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Fees\Models\Invoice;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'month' => '2026-01',
            'total' => 100000,
            'paid' => 0,
            'due' => 100000,
            'status' => 'unpaid',
            'due_date' => '2026-01-10',
        ];
    }
}
