<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Student\Models\Enrollment;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use App\Support\Tenancy\CurrentSchool;
use Carbon\Carbon;

class RecordAttendanceAction
{
    /**
     * Record or update attendance for an enrollment on a specific date.
     */
    public function execute(
        Enrollment $enrollment,
        string $date,
        AttendanceStatus $status,
        ?User $marker = null,
        ?string $clientUuid = null,
    ): AttendanceRecord {
        return CurrentSchool::runAs(
            (int) $enrollment->school_id,
            fn (): AttendanceRecord => $this->record($enrollment, $date, $status, $marker, $clientUuid),
        );
    }

    private function record(
        Enrollment $enrollment,
        string $date,
        AttendanceStatus $status,
        ?User $marker,
        ?string $clientUuid,
    ): AttendanceRecord {
        $schoolId = $enrollment->school_id;
        $formattedDate = Carbon::parse($date)->format('Y-m-d');

        $record = AttendanceRecord::withoutGlobalScope('school')
            ->where('school_id', $schoolId)
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('date', $formattedDate)
            ->first();

        if ($record) {
            $record->update([
                'status' => $status,
                'marked_by' => $marker?->id ?? auth()->id(),
                'client_uuid' => $clientUuid ?? $record->client_uuid,
                'synced_at' => now(),
            ]);

            return $record;
        }

        return AttendanceRecord::create([
            'school_id' => $schoolId,
            'enrollment_id' => $enrollment->id,
            'date' => $formattedDate,
            'status' => $status,
            'marked_by' => $marker?->id ?? auth()->id(),
            'client_uuid' => $clientUuid,
            'synced_at' => now(),
        ]);
    }
}
