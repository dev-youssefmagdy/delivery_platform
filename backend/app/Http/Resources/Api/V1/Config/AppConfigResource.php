<?php

namespace App\Http\Resources\Api\V1\Config;

use App\DTOs\Config\AppConfigData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * Wire shape of `GET /api/v1/config/app` (docs/API.md §8.4). Empty maps are emitted as `{}` so clients can
 * always parse them as objects.
 *
 * @property AppConfigData $resource
 */
class AppConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $config = $this->resource;

        return [
            'default_locale' => $config->defaultLocale,
            'locales' => $config->locales,
            'country' => $config->country,
            'currency' => $config->currency === null ? null : [
                'code' => $config->currency['code'],
                'exponent' => $config->currency['exponent'],
                'symbol' => ['ar' => $config->currency['symbol_ar'], 'en' => $config->currency['symbol_en']],
            ],
            'digits_preference' => $config->digitsPreference,
            'support' => $config->support,
            'maintenance' => $config->maintenance,
            'apps' => $config->apps,
            'policies' => $config->policies === [] ? new stdClass : $config->policies,
            'features' => $config->features === [] ? new stdClass : $config->features,
            'safety' => $config->safety === [] ? new stdClass : $config->safety,
            'flags' => $config->flags === [] ? new stdClass : $config->flags,
        ];
    }
}
