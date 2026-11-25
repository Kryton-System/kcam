<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Religion recorded on a student's admission form.
 */
enum Religion: string
{
    case Islam = 'islam';
    case Hinduism = 'hinduism';
    case Buddhism = 'buddhism';
    case Christianity = 'christianity';
    case Other = 'other';

    public function label(): string
    {
        return __('students.religions.'.$this->value);
    }
}
