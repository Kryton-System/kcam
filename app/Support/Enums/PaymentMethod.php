<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Payment method types.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Rocket = 'rocket';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    public function labelBn(): string
    {
        return match ($this) {
            self::Cash => 'নগদ',
            self::Bkash => 'বিকাশ',
            self::Nagad => 'নগদ (মোবাইল)',
            self::Rocket => 'রকেট',
            self::Card => 'কার্ড',
            self::BankTransfer => 'ব্যাংক ট্রান্সফার',
        };
    }
}
