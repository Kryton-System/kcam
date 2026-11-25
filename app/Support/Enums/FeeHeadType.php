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

    public function label(): string
    {
        return __('enums.fee_head_type.'.$this->value);
    }
}
