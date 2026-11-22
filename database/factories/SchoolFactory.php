<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Kindergarten',
            'name_bn' => 'গ্রিন ভ্যালি কিন্ডারগার্টেন',
            'logo' => null,
            'eiin' => (string) fake()->numerify('######'),
            'phone' => '01'.fake()->numerify('#########'),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'address_bn' => 'ধানমন্ডি, ঢাকা',
            'plan_id' => 'starter',
            'status' => 'active',
            'settings' => [
                'currency' => 'BDT',
                'sms_enabled' => true,
                'timezone' => 'Asia/Dhaka',
            ],
        ];
    }
}
