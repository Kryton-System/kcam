<?php

declare(strict_types=1);

namespace App\Support\Helpers;

/**
 * Bangla language helpers for number formatting, date display,
 * and text utilities.
 */
final class BanglaHelper
{
    /**
     * Bangla digit characters indexed by their ASCII digit equivalents.
     */
    private const BANGLA_DIGITS = [
        '0' => '০',
        '1' => '১',
        '2' => '২',
        '3' => '৩',
        '4' => '৪',
        '5' => '৫',
        '6' => '৬',
        '7' => '৭',
        '8' => '৮',
        '9' => '৯',
    ];

    /**
     * Bangla month names.
     *
     * @var array<int, string>
     */
    private const BANGLA_MONTHS = [
        1 => 'জানুয়ারি',
        2 => 'ফেব্রুয়ারি',
        3 => 'মার্চ',
        4 => 'এপ্রিল',
        5 => 'মে',
        6 => 'জুন',
        7 => 'জুলাই',
        8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর',
        10 => 'অক্টোবর',
        11 => 'নভেম্বর',
        12 => 'ডিসেম্বর',
    ];

    /**
     * Bangla day names.
     *
     * @var array<int, string>
     */
    private const BANGLA_DAYS = [
        0 => 'রবিবার',
        1 => 'সোমবার',
        2 => 'মঙ্গলবার',
        3 => 'বুধবার',
        4 => 'বৃহস্পতিবার',
        5 => 'শুক্রবার',
        6 => 'শনিবার',
    ];

    /**
     * Convert ASCII digits to Bangla digits.
     */
    public static function toBanglaDigits(string|int|float $number): string
    {
        $str = (string) $number;

        return strtr($str, self::BANGLA_DIGITS);
    }

    /**
     * Convert Bangla digits to ASCII digits.
     */
    public static function toEnglishDigits(string $banglaNumber): string
    {
        return strtr($banglaNumber, array_flip(self::BANGLA_DIGITS));
    }

    /**
     * Format a number as Bangla currency (Taka).
     * Amount is in poisha (smallest unit) or taka depending on $isPoisha.
     */
    public static function formatTaka(int|float $amount, bool $isPoisha = false): string
    {
        $taka = $isPoisha ? $amount / 100 : $amount;

        $formatted = number_format((float) $taka, 2, '.', ',');

        return '৳'.self::toBanglaDigits($formatted);
    }

    /**
     * Format a date as Bangla date string.
     * Example: "১৫ নভেম্বর, ২০২৬"
     */
    public static function formatDate(\DateTimeInterface|string $date): string
    {
        if (is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }

        $day = self::toBanglaDigits($date->format('j'));
        $month = self::BANGLA_MONTHS[(int) $date->format('n')];
        $year = self::toBanglaDigits($date->format('Y'));

        return "{$day} {$month}, {$year}";
    }

    /**
     * Get Bangla day name.
     */
    public static function dayName(\DateTimeInterface|string $date): string
    {
        if (is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }

        return self::BANGLA_DAYS[(int) $date->format('w')];
    }

    /**
     * Get Bangla month name.
     */
    public static function monthName(int $month): string
    {
        return self::BANGLA_MONTHS[$month] ?? '';
    }

    /**
     * Format an ordinal number in Bangla (1st, 2nd, etc.).
     */
    public static function ordinal(int $number): string
    {
        $bangla = self::toBanglaDigits($number);

        // In Bangla, ordinals use suffixes -ম, -য়, -ই depending on the number.
        // Simplified: append "ম" for most cases.
        return $bangla.'ম';
    }
}
