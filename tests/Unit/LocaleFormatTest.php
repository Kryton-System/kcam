<?php

declare(strict_types=1);

use App\Support\Helpers\LocaleFormat;

afterEach(function (): void {
    app()->setLocale('bn');
});

test('digits follow the Bangla locale', function (): void {
    app()->setLocale('bn');
    expect(LocaleFormat::digits(2026))->toBe('২০২৬')
        ->and(LocaleFormat::digits('01711-111'))->toBe('০১৭১১-১১১');
});

test('digits stay English in the English locale', function (): void {
    app()->setLocale('en');
    expect(LocaleFormat::digits(2026))->toBe('2026')
        ->and(LocaleFormat::digits('01711-111'))->toBe('01711-111');
});

test('taka amounts are shown in poisha with the taka sign and locale digits', function (): void {
    app()->setLocale('bn');
    expect(LocaleFormat::taka(123450))->toBe('৳১,২৩৪.৫০');

    app()->setLocale('en');
    expect(LocaleFormat::taka(123450))->toBe('৳1,234.50');
});

test('numbers are grouped with locale digits', function (): void {
    app()->setLocale('bn');
    expect(LocaleFormat::number(1234))->toBe('১,২৩৪')
        ->and(LocaleFormat::number(0))->toBe('০');

    app()->setLocale('en');
    expect(LocaleFormat::number(1234))->toBe('1,234');
});

test('dates use Bangla month names in Bangla and English month names in English', function (): void {
    app()->setLocale('bn');
    expect(LocaleFormat::date('2026-11-15'))->toBe('১৫ নভেম্বর, ২০২৬');

    app()->setLocale('en');
    expect(LocaleFormat::date('2026-11-15'))->toBe('15 Nov 2026');
});

test('date is empty when no value is given', function (): void {
    expect(LocaleFormat::date(null))->toBe('');
});

test('month names follow the locale', function (): void {
    app()->setLocale('bn');
    expect(LocaleFormat::monthName(11))->toBe('নভেম্বর');

    app()->setLocale('en');
    expect(LocaleFormat::monthName(11))->toBe('November');
});

test('mobile numbers keep their 01 format in both locales', function (): void {
    app()->setLocale('bn');
    expect(LocaleFormat::mobile('01711111111'))->toBe('০১৭১১১১১১১১');

    app()->setLocale('en');
    expect(LocaleFormat::mobile('01711111111'))->toBe('01711111111')
        ->and(LocaleFormat::mobile(null))->toBe('');
});
