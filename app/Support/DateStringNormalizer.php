<?php

namespace App\Support;

class DateStringNormalizer
{
    public static function toIso(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '' || $text === '---') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $text, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    public static function toDisplay(mixed $value): string
    {
        $iso = self::toIso($value);
        if ($iso === null) {
            return trim((string) $value);
        }

        [$year, $month, $day] = explode('-', $iso);

        return "{$day}/{$month}/{$year}";
    }
}
