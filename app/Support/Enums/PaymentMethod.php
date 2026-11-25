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

    public function label(): string
    {
        return __('enums.payment_method.'.$this->value);
    }
}
