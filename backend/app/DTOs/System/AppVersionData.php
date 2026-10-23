<?php

namespace App\DTOs\System;

use App\Enums\System\AppPlatform;
use App\Enums\System\AppTarget;
use Illuminate\Http\Request;

/**
 * Admin input for one app version row.
 */
final readonly class AppVersionData
{
    public function __construct(
        public AppTarget $app,
        public AppPlatform $platform,
        public string $minVersion,
        public ?string $latestVersion,
        public bool $forceUpdate,
        public ?string $storeUrl,
        public ?int $lockVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $latest = $request->input('latest_version');
        $url = $request->input('store_url');
        $lock = $request->input('lock_version');

        return new self(
            AppTarget::tryFromValue($request->input('app')) ?? AppTarget::Customer,
            AppPlatform::tryFromValue($request->input('platform')) ?? AppPlatform::Android,
            trim((string) $request->input('min_version', '1.0.0')),
            is_string($latest) && trim($latest) !== '' ? trim($latest) : null,
            $request->boolean('force_update'),
            is_string($url) && trim($url) !== '' ? trim($url) : null,
            is_numeric($lock) ? (int) $lock : null,
        );
    }
}
