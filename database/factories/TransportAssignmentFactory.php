<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Transport\Models\TransportAssignment;
use App\Domain\Transport\Models\TransportRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransportAssignmentFactory extends Factory
{
    protected $model = TransportAssignment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'route_id' => TransportRoute::factory(),
            'enrollment_id' => Enrollment::factory(),
            'stop' => 'Mirpur 10',
            'monthly_fee' => 80000,
        ];
    }
}
