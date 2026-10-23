<?php

namespace App\DTOs\Concerns;

/**
 * Small normalisers for validated form input (trimmed strings, ar/en maps, nullable ids).
 */
trait NormalizesInput
{
    /**
     * @return array<string, string|null> locale => trimmed text or null
     */
    protected static function translations(mixed $input): array
    {
        $translations = [];

        foreach ((array) $input as $locale => $value) {
            $translations[(string) $locale] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return $translations;
    }

    /**
     * Only the locales that have text (what is stored in the JSON column).
     *
     * @param array<string, string|null> $translations
     * @return array<string, string>
     */
    protected static function filled(array $translations): array
    {
        return array_filter($translations, static fn (?string $value): bool => $value !== null);
    }

    protected static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected static function id(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
