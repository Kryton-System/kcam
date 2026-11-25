<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Payment transaction status.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.payment_status.'.$this->value);
    }
}
