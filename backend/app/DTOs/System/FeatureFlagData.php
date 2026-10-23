<?php

namespace App\DTOs\System;

use App\Enums\System\AppTarget;
use App\Enums\System\FeatureFlagKey;
use Illuminate\Http\Request;

/**
 * Admin input for one feature flag: switch, targeting (apps, cities) and rollout percentage.
 */
final readonly class FeatureFlagData
{
    /**
     * @param list<string> $apps
     * @param list<int> $cityIds
     */
    public function __construct(
        public FeatureFlagKey $key,
        public bool $enabled,
        public array $apps,
        public array $cityIds,
        public int $rolloutPercent,
        public ?string $note,
        public ?int $lockVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $apps = array_values(array_filter(array_map(static fn (mixed $v): ?string => AppTarget::tryFromValue(is_string($v) ? $v : null)?->value, (array) $request->input('apps', []))));
        $cities = array_values(array_unique(array_map('intval', array_filter((array) $request->input('city_ids', []), 'is_numeric'))));
        $note = $request->input('note');
        $lock = $request->input('lock_version');

        return new self(
            FeatureFlagKey::tryFromValue($request->input('key')) ?? FeatureFlagKey::Wallet,
            $request->boolean('enabled'),
            $apps,
            $cities,
            max(0, min(100, (int) $request->input('rollout_percent', 100))),
            is_string($note) && trim($note) !== '' ? trim($note) : null,
            is_numeric($lock) ? (int) $lock : null,
        );
    }
}
