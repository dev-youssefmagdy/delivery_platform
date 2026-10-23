<?php

namespace App\Support\Content;

use App\Models\Contracts\TranslatedContent;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class Translations
{
    /**
     * The trimmed text stored for exactly this language (no fallback); empty when there is none.
     */
    public static function exact(Model $content, string $attribute, string $locale): string
    {
        if (! $content instanceof TranslatedContent) {
            throw new LogicException($content::class.' has no translated attributes.');
        }

        return trim((string) $content->getTranslation($attribute, $locale, false));
    }
}
