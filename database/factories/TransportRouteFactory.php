<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\Transport\Models\TransportRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransportRouteFactory extends Factory
{
    protected $model = TransportRoute::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'vehicle_id' => null,
            'name' => 'Route '.fake()->unique()->numberBetween(1, 99999),
            'name_bn' => 'মিরপুর রুট',
            'stops' => "Mirpur 10\nMirpur 11\nPallabi",
        ];
    }
}
