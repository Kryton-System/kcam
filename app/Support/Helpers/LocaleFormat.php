<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Display formatting that follows the visitor's language.
 *
 * Bangla mode shows Bangla digits and Bangla month names. English mode shows ASCII digits and English names.
 * Stored values are never changed here, only how they are shown.
 */
final class LocaleFormat
{
    public static function digits(string|int|float $value): string
    {
        $text = (string) $value;

        return self::isBangla() ? BanglaHelper::toBanglaDigits($text) : $text;
    }

    /**
     * Grouped number with the given decimals, e.g. 1,234 or ১,২৩৪.
     */
    public static function number(int|float $value, int $decimals = 0): string
    {
        return self::digits(number_format((float) $value, $decimals, '.', ','));
    }

    /**
     * Money stored in poisha (default) or taka, shown with the taka sign and two decimals.
     */
    public static function taka(int|float $amount, bool $isPoisha = true): string
    {
        $taka = $isPoisha ? $amount / 100 : $amount;

        return '৳'.self::digits(number_format((float) $taka, 2, '.', ','));
    }

    /**
     * Bangla: "১৫ নভেম্বর, ২০২৬". English: "15 Nov 2026".
     */
    public static function date(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $date = is_string($date) ? new DateTimeImmutable($date) : $date;

        if (! self::isBangla()) {
            return $date->format('j M Y');
        }

        return self::digits($date->format('j')).' '.BanglaHelper::monthName((int) $date->format('n')).', '.self::digits($date->format('Y'));
    }

    public static function monthName(int $month): string
    {
        if (self::isBangla()) {
            return BanglaHelper::monthName($month);
        }

        return DateTimeImmutable::createFromFormat('!n', (string) $month)->format('F');
    }

    public static function dayName(DateTimeInterface|string $date): string
    {
        $date = is_string($date) ? new DateTimeImmutable($date) : $date;

        return self::isBangla() ? BanglaHelper::dayName($date) : $date->format('l');
    }

    /**
     * Mobile numbers are stored as 01XXXXXXXXX and shown the same way, only the digits change.
     */
    public static function mobile(?string $mobile): string
    {
        return $mobile === null || $mobile === '' ? '' : self::digits($mobile);
    }

    private static function isBangla(): bool
    {
        return app()->isLocale('bn');
    }
}
