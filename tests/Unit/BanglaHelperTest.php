<?php

declare(strict_types=1);

use App\Support\Helpers\BanglaHelper;

test('it converts ascii digits to bangla digits', function (): void {
    expect(BanglaHelper::toBanglaDigits(1234567890))->toBe('১২৩৪৫৬৭৮৯০');
    expect(BanglaHelper::toBanglaDigits('01712345678'))->toBe('০১৭১২৩৪৫৬৭৮');
    expect(BanglaHelper::toBanglaDigits('Class 5, Roll 12'))->toBe('Class ৫, Roll ১২');
});

test('it converts bangla digits to english digits', function (): void {
    expect(BanglaHelper::toEnglishDigits('১২৩৪৫৬৭৮৯০'))->toBe('1234567890');
    expect(BanglaHelper::toEnglishDigits('০১৭১২৩৪৫৬৭৮'))->toBe('01712345678');
});

test('it formats amount in taka with bangla digits and symbol', function (): void {
    expect(BanglaHelper::formatTaka(1500))->toBe('৳১,৫০০.০০');
    expect(BanglaHelper::formatTaka(500.50))->toBe('৳৫০০.৫০');
    expect(BanglaHelper::formatTaka(150000, true))->toBe('৳১,৫০০.০০'); // 150000 poisha = 1500 taka
});

test('it formats date in bangla', function (): void {
    $date = new DateTimeImmutable('2026-03-26');
    expect(BanglaHelper::formatDate($date))->toBe('২৬ মার্চ, ২০২৬');
});

test('it returns correct bangla day name', function (): void {
    // 2026-03-26 is Thursday
    $date = new DateTimeImmutable('2026-03-26');
    expect(BanglaHelper::dayName($date))->toBe('বৃহস্পতিবার');
});

test('it returns correct bangla month name', function (): void {
    expect(BanglaHelper::monthName(1))->toBe('জানুয়ারি');
    expect(BanglaHelper::monthName(2))->toBe('ফেব্রুয়ারি');
    expect(BanglaHelper::monthName(12))->toBe('ডিসেম্বর');
});

test('it returns bangla ordinal', function (): void {
    expect(BanglaHelper::ordinal(1))->toBe('১ম');
    expect(BanglaHelper::ordinal(5))->toBe('৫ম');
});
