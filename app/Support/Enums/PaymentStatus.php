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

    public function labelBn(): string
    {
        return match ($this) {
            self::Pending => 'অপেক্ষমান',
            self::Completed => 'সম্পন্ন',
            self::Failed => 'ব্যর্থ',
            self::Refunded => 'ফেরত',
            self::Cancelled => 'বাতিল',
        };
    }
}
