<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'class_id' => SchoolClass::factory(),
            'name' => fake()->randomElement(['A', 'B', 'Rose', 'Lotus']),
            'name_bn' => fake()->randomElement(['ক', 'খ', 'গোলাপ', 'পদ্ম']),
        ];
    }
}
