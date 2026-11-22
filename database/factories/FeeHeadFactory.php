<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Fees\Models\FeeHead;
use App\Domain\School\Models\School;
use App\Support\Enums\FeeHeadType;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeHeadFactory extends Factory
{
    protected $model = FeeHead::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->randomElement(['Monthly Tuition', 'Admission Fee', 'Exam Fee', 'Session Fee']),
            'name_bn' => fake()->randomElement(['মাসিক বেতন', 'ভর্তি ফি', 'পরীক্ষার ফি', 'সেশন ফি']),
            'type' => FeeHeadType::Monthly,
            'is_active' => true,
        ];
    }
}
