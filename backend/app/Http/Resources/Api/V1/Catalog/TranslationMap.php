<?php

namespace App\Http\Resources\Api\V1\Catalog;

use stdClass;

/**
 * Translation maps are always JSON objects (`{}` when empty) so clients can parse them as maps.
 */
final class TranslationMap
{
    /**
     * @param array<string, mixed> $translations
     * @return array<string, mixed>|stdClass
     */
    public static function of(array $translations): array|stdClass
    {
        return $translations === [] ? new stdClass : $translations;
    }
}
