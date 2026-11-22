<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Fee head types.
 */
enum FeeHeadType: string
{
    case Monthly = 'monthly';
    case OneTime = 'one_time';
    case Yearly = 'yearly';

    public function labelBn(): string
    {
        return match ($this) {
            self::Monthly => 'মাসিক',
            self::OneTime => 'এককালীন',
            self::Yearly => 'বার্ষিক',
        };
    }
}
