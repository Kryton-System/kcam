<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Student enrollment status.
 */
enum StudentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Transferred = 'transferred';
    case Graduated = 'graduated';
    case Dropped = 'dropped';

    public function labelBn(): string
    {
        return match ($this) {
            self::Active => 'সক্রিয়',
            self::Inactive => 'নিষ্ক্রিয়',
            self::Transferred => 'বদলি',
            self::Graduated => 'পাশ',
            self::Dropped => 'ঝরে পড়া',
        };
    }
}
