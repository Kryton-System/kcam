<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Enrollment;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'enrollment_id' => Enrollment::factory(),
            'date' => fake()->date('Y-m-d'),
            'status' => AttendanceStatus::Present,
            'marked_by' => User::factory(),
            'client_uuid' => fake()->uuid(),
            'synced_at' => now(),
        ];
    }
}
