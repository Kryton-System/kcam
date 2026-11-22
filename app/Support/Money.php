<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Money is always whole poisha (1 taka = 100 poisha) held in an int. No floats anywhere.
 */
final class Money
{
    private const MAX_TAKA_DIGITS = 9;

    /**
     * Parse a taka string such as "500", "500.5" or "500.50" into poisha.
     *
     * @throws InvalidArgumentException when the text is not a non negative amount with at most two decimals
     */
    public static function toPoisha(string $taka): int
    {
        if (preg_match('/^(\d{1,'.self::MAX_TAKA_DIGITS.'})(?:\.(\d{1,2}))?$/', $taka, $match) !== 1) {
            throw new InvalidArgumentException("Invalid taka amount: {$taka}");
        }

        $fraction = str_pad($match[2] ?? '', 2, '0');

        return ((int) $match[1]) * 100 + (int) $fraction;
    }

    /**
     * Format poisha as a plain taka string with two decimals, e.g. 50050 becomes "500.50".
     */
    public static function toTaka(int $poisha): string
    {
        $sign = $poisha < 0 ? '-' : '';
        $poisha = abs($poisha);

        return sprintf('%s%d.%02d', $sign, intdiv($poisha, 100), $poisha % 100);
    }

    /**
     * The part of an amount a percentage rate covers, rounded half up to a whole poisha.
     * The rate is in basis points: 10% is 1000, 12.5% is 1250.
     */
    public static function percentOf(int $poisha, int $basisPoints): int
    {
        return intdiv($poisha * $basisPoints + 5000, 10000);
    }
}
