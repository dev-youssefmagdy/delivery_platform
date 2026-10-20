<?php

namespace App\Support\Vehicles;

/**
 * Licence plate handling. The comparison form has latin digits, no spaces or punctuation and upper-case latin letters;
 * Arabic letters are kept as typed (Egyptian plates mix Arabic letters and digits), so the same plate written with
 * Arabic-Indic or Western digits, spaces or dashes is one plate.
 */
final class PlateNumber
{
    private const DIGITS = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];

    public static function normalize(string $plate): string
    {
        $latin = strtr($plate, self::DIGITS);

        return mb_strtoupper((string) preg_replace('/[^\p{L}\p{N}]+/u', '', $latin));
    }

    public static function display(string $plate): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $plate)), 0, 20);
    }

    /**
     * 3 to 12 letters/digits, at least one digit.
     */
    public static function isPlausible(string $normalized): bool
    {
        return preg_match('/^[\p{L}\p{N}]{3,12}$/u', $normalized) === 1 && preg_match('/\p{N}/u', $normalized) === 1;
    }
}
