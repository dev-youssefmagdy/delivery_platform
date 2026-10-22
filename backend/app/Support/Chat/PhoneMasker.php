<?php

namespace App\Support\Chat;

/**
 * Hides phone numbers typed into a chat message (SRS §14.2: "mask phone numbers or use a relay"): any run of eight or more
 * digits, with the usual separators, becomes a mask. Contact goes through the call button, not through the text.
 */
final class PhoneMasker
{
    public const MASK = '•••••••••';

    public static function mask(string $text): string
    {
        if (! (bool) config('support.chat.mask_phone_numbers', true)) {
            return $text;
        }

        return (string) preg_replace_callback('/(?<![\w])\+?[\d\x{0660}-\x{0669}](?:[\s().\-]?[\d\x{0660}-\x{0669}]){7,}(?![\w])/u', static fn (): string => self::MASK, $text);
    }
}
