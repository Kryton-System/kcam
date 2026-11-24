<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'স্টকে যোগ / Stock in',
            self::Out => 'স্টক থেকে বের / Stock out',
            self::Adjustment => 'সমন্বয় / Adjustment',
        };
    }
}
