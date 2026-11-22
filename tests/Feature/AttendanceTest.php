<?php

declare(strict_types=1);

use App\Domain\Attendance\Actions\RecordAttendanceAction;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Enrollment;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('record attendance action creates or updates attendance idempotently', function (): void {
    $school = School::factory()->create();
    $teacher = User::factory()->teacher()->create(['school_id' => $school->id]);
    $enrollment = Enrollment::factory()->create(['school_id' => $school->id]);

    $action = app(RecordAttendanceAction::class);
    $date = '2026-03-01';
    $clientUuid = (string) Str::uuid();

    // Mark as Present
    $record = $action->execute(
        enrollment: $enrollment,
        date: $date,
        status: AttendanceStatus::Present,
        marker: $teacher,
        clientUuid: $clientUuid,
    );

    expect($record)->toBeInstanceOf(AttendanceRecord::class)
        ->and($record->status)->toBe(AttendanceStatus::Present)
        ->and($record->school_id)->toBe($school->id)
        ->and($record->enrollment_id)->toBe($enrollment->id);

    // Call again on same date with Absent (e.g. corrected or offline sync)
    $updated = $action->execute(
        enrollment: $enrollment,
        date: $date,
        status: AttendanceStatus::Absent,
        marker: $teacher,
        clientUuid: $clientUuid,
    );

    // Reads need a school context: act as the teacher
    $this->actingAs($teacher);

    // Should update existing record, not create duplicate
    expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->where('date', $date)->count())->toBe(1)
        ->and($updated->id)->toBe($record->id)
        ->and($updated->status)->toBe(AttendanceStatus::Absent);
});
