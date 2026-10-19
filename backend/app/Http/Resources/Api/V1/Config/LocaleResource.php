<?php

namespace App\Http\Resources\Api\V1\Config;

use App\Enums\Common\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One supported locale. `name` is the native name, so the payload is identical for every request language.
 *
 * @property array{locale: Locale, is_default: bool} $resource
 */
class LocaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $this->resource['locale'];

        return [
            'code' => $locale->value,
            'name' => __("enums.locale.{$locale->value}", [], $locale->value),
            'direction' => $locale->direction(),
            'is_default' => $this->resource['is_default'],
        ];
    }
}
