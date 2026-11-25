<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Attendance status values.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return __('enums.attendance_status.'.$this->value);
    }
}
