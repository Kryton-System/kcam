<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\Transport\Models\TransportVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransportVehicleFactory extends Factory
{
    protected $model = TransportVehicle::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'van_number' => 'Dhaka Metro '.fake()->unique()->numberBetween(10000, 99999),
            'driver_name' => 'আব্দুর রহিম',
            'driver_mobile' => '017'.fake()->numerify('########'),
            'capacity' => 10,
        ];
    }
}
