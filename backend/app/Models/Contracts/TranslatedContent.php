<?php

namespace App\Models\Contracts;

/**
 * A model whose text columns are stored per language (Spatie HasTranslations provides these methods).
 */
interface TranslatedContent
{
    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true): mixed;
}
