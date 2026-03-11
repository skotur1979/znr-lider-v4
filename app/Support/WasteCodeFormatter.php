<?php

namespace App\Support;

class WasteCodeFormatter
{
    public static function plain(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        $raw = trim($value);
        $hasStar = str_ends_with($raw, '*');

        $code = rtrim($raw, '*');

        $digits = preg_replace('/\D+/', '', $code);

        if (strlen($digits) === 6) {
            $code =
                substr($digits, 0, 2) . ' ' .
                substr($digits, 2, 2) . ' ' .
                substr($digits, 4, 2);
        }

        return $hasStar ? $code . '*' : $code;
    }

    public static function html(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        $raw = trim($value);
        $hasStar = str_ends_with($raw, '*');

        $code = self::plain($value);
        $code = rtrim($code, '*');

        return $hasStar
            ? e($code) . '<sup style="font-size:0.90em; position:relative; top:-0.40em;">*</sup>'
            : e($code);
    }

    public static function isHazardous(?string $value): bool
    {
        return str_ends_with((string) $value, '*');
    }
}