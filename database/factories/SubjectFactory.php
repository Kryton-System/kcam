<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'class_id' => SchoolClass::factory(),
            'name' => fake()->randomElement(['Bangla', 'English', 'Mathematics', 'General Science']),
            'name_bn' => fake()->randomElement(['বাংলা', 'ইংরেজি', 'গণিত', 'সাধারণ বিজ্ঞান']),
            'code' => fake()->lexify('SUB-???'),
            'full_marks' => 100,
            'pass_marks' => 33,
            'is_optional' => false,
        ];
    }
}
