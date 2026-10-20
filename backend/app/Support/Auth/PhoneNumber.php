<?php

namespace App\Support\Auth;

/**
 * Minimal E.164 handling for the identity flow. Clients send the full international number (the app's phone
 * field produces `+201001234567`); Arabic-Indic digits and common separators are tolerated.
 */
final class PhoneNumber
{
    public const PATTERN = '/^\+[1-9][0-9]{7,14}$/';

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = strtr(trim($raw), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $digits = (string) preg_replace('/[\s\-().]/u', '', $digits);

        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }

        return preg_match(self::PATTERN, $digits) === 1 ? $digits : null;
    }

    /**
     * `+201001234567` -> `+20•••••4567`: enough for support to recognise a number, not enough to dial it.
     */
    public static function mask(string $e164): string
    {
        $length = strlen($e164);

        return $length <= 8
            ? substr($e164, 0, 2).str_repeat('•', max(0, $length - 2))
            : substr($e164, 0, 3).str_repeat('•', $length - 7).substr($e164, -4);
    }

    /**
     * Keyed hash used to find a phone's OTP challenges without storing the number in that table.
     */
    public static function hash(string $e164): string
    {
        return hash_hmac('sha256', $e164, self::key());
    }

    public static function key(): string
    {
        return (string) config('app.key');
    }
}
