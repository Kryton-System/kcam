<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\Staff\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'name_bn' => 'সুলতানা পারভীন',
            'designation' => fake()->randomElement(['Assistant Teacher', 'Head Teacher', 'Accountant']),
            'phone' => '01'.fake()->numerify('#########'),
            'joined_on' => fake()->date('Y-m-d', '-2 years'),
            'salary_basic' => fake()->randomElement([1200000, 1500000, 2000000]),
            'status' => 'active',
        ];
    }
}
