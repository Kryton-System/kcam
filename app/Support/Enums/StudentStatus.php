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

    public function label(): string
    {
        return __('enums.student_status.'.$this->value);
    }
}
