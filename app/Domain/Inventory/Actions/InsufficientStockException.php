<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use DomainException;

class InsufficientStockException extends DomainException
{
    public static function for(int $available, int $requested): self
    {
        return new self("Only {$available} in stock, cannot remove {$requested}.");
    }
}
