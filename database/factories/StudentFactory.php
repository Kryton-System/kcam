<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use App\Support\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_code' => 'STU-'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'name_bn' => 'আরিফুল ইসলাম',
            'dob' => fake()->date('Y-m-d', '-5 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_reg_no' => fake()->numerify('#################'),
            'photo' => null,
            'blood_group' => fake()->randomElement(['A+', 'B+', 'O+', 'AB+']),
            'address' => fake()->address(),
            'status' => StudentStatus::Active,
        ];
    }
}
