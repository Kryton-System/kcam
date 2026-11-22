<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => 'Class '.fake()->unique()->numberBetween(1, 99999),
            'name_bn' => 'শ্রেণি '.fake()->randomElement(['১ম', '২য়', '৩য়', '৪র্থ', '৫ম']),
            'numeric_name' => fake()->numberBetween(1, 5),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
