<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Config\AppConfigResource;
use App\Http\Resources\Api\V1\Config\LocaleResource;
use App\Services\Config\AppConfigService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public, cacheable configuration endpoints (SRS §3, §16.15): app config and supported locales. The route has
 * `cache.headers` (ETag + short max-age), so unchanged config answers `304`.
 */
final class ConfigController extends Controller
{
    private const HEADERS = ['Vary' => 'Accept-Language'];

    public function __construct(private readonly AppConfigService $config) {}

    public function app(): JsonResponse
    {
        return ApiResponse::success(data: new AppConfigResource($this->config->get()), headers: self::HEADERS);
    }

    public function locales(): JsonResponse
    {
        $default = $this->config->defaultLocale();
        $items = array_map(
            static fn ($locale): array => ['locale' => $locale, 'is_default' => $locale === $default],
            $this->config->supportedLocales(),
        );

        return ApiResponse::success(data: LocaleResource::collection($items), headers: self::HEADERS);
    }
}
