<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\Helpers\BanglaHelper;
use Normalizer;

/**
 * Normalise raw input before validation runs. Escaping for output is a separate job:
 * values are stored as typed (minus control characters) and escaped where they are shown.
 */
trait NormalizesInput
{
    /**
     * @param  list<string>  $textFields  free text: control characters removed, NFC, trimmed
     * @param  list<string>  $digitFields  numbers and dates: Bangla digits become ASCII digits
     * @param  list<string>  $mobileFields  Bangladesh mobile numbers: canonical 01XXXXXXXXX form
     */
    protected function normalizeInput(array $textFields = [], array $digitFields = [], array $mobileFields = []): void
    {
        $changes = [];

        foreach ($textFields as $field) {
            if (is_string($this->input($field))) {
                $changes[$field] = self::cleanText($this->input($field));
            }
        }

        foreach ($digitFields as $field) {
            if (is_string($this->input($field))) {
                $changes[$field] = BanglaHelper::toEnglishDigits(trim($this->input($field)));
            }
        }

        foreach ($mobileFields as $field) {
            if (is_string($this->input($field))) {
                $changes[$field] = self::canonicalMobile($this->input($field));
            }
        }

        $this->merge($changes);
    }

    public static function cleanText(string $value): string
    {
        // Keep tab, newline and carriage return; keep ZWJ and ZWNJ which Bangla conjuncts need.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        if (class_exists(Normalizer::class)) {
            $clean = Normalizer::normalize($clean, Normalizer::FORM_C) ?: $clean;
        }

        return trim($clean);
    }

    public static function canonicalMobile(string $value): string
    {
        $digits = preg_replace('/[\s\-().]/', '', BanglaHelper::toEnglishDigits(trim($value))) ?? $value;

        if (preg_match('/^(?:\+?88)?(01[3-9]\d{8})$/', $digits, $match) === 1) {
            return $match[1];
        }

        return $digits;
    }
}
