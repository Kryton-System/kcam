<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'name' => fake()->name(),
            'name_bn' => 'রফিকুল ইসলাম',
            'relation' => fake()->randomElement(['father', 'mother', 'guardian']),
            'mobile' => '01'.fake()->numerify('#########'),
            'nid' => fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'is_primary' => true,
        ];
    }
}
