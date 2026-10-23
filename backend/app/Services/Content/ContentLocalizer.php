<?php

namespace App\Services\Content;

use App\Enums\Common\Locale;
use Illuminate\Database\Eloquent\Model;

/**
 * Locale resolution for content: the requested language when it has text, otherwise Arabic when the item is
 * configured to fall back (`fallback_to_ar`, default on), otherwise nothing (the item is not served in that language).
 */
final class ContentLocalizer
{
    public function text(Model $content, string $attribute, string $locale): ?string
    {
        /** @var \Spatie\Translatable\HasTranslations&Model $content */
        $value = trim((string) $content->getTranslation($attribute, $locale, false));

        if ($value !== '') {
            return $value;
        }

        if ($locale !== Locale::default()->value && $this->fallsBack($content)) {
            $value = trim((string) $content->getTranslation($attribute, Locale::default()->value, false));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $translations locale => text
     */
    public function pick(array $translations, string $locale, bool $fallback): ?string
    {
        $value = trim((string) ($translations[$locale] ?? ''));

        if ($value === '' && $fallback && $locale !== Locale::default()->value) {
            $value = trim((string) ($translations[Locale::default()->value] ?? ''));
        }

        return $value !== '' ? $value : null;
    }

    /**
     * Is the item served in $locale? Arabic always is; another language needs its own title/question or the fallback.
     */
    public function available(Model $content, string $primaryAttribute, string $locale): bool
    {
        return $this->text($content, $primaryAttribute, $locale) !== null;
    }

    /**
     * The language actually served for an attribute.
     */
    public function servedLocale(Model $content, string $attribute, string $locale): string
    {
        /** @var \Spatie\Translatable\HasTranslations&Model $content */
        return trim((string) $content->getTranslation($attribute, $locale, false)) !== '' ? $locale : Locale::default()->value;
    }

    private function fallsBack(Model $content): bool
    {
        return (bool) ($content->getAttribute('fallback_to_ar') ?? true);
    }
}
