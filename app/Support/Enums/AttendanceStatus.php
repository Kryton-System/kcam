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

    public function labelBn(): string
    {
        return match ($this) {
            self::Present => 'উপস্থিত',
            self::Absent => 'অনুপস্থিত',
            self::Late => 'বিলম্বিত',
            self::Excused => 'ছুটি',
        };
    }
}
