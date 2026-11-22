<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Notices\Models\SmsLog;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsLogFactory extends Factory
{
    protected $model = SmsLog::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'to' => '01'.fake()->numerify('#########'),
            'body' => 'Your child was absent today.',
            'provider_ref' => fake()->uuid(),
            'status' => 'delivered',
            'cost' => 35,
            'sent_at' => now(),
        ];
    }
}
