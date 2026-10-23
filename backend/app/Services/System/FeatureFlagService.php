<?php

namespace App\Services\System;

use App\DTOs\System\FeatureFlagData;
use App\DTOs\System\FlagContext;
use App\Enums\Common\AuditAction;
use App\Enums\System\FeatureFlagKey;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\FeatureFlag;
use App\Repositories\Contracts\System\FeatureFlagRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use App\Support\Config\AppConfigCache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The one place that answers "is this feature on?" (SRS 16.15) and the one place that changes flags.
 *
 * Evaluation (`isEnabled`): no row -> the key's default (the legacy `feature_*` setting for wallet / offers, else off);
 * switch off -> off; an app list the caller is not in -> off; a city list the caller's city is not in -> off (unknown city -> off);
 * rollout 100 -> on, 0 -> off, otherwise a stable bucket crc32("<key>|<subject>") % 100 below the percentage (no subject -> off).
 * The bucket is stable per subject and flag, so raising the percentage only adds people.
 */
final class FeatureFlagService
{
    public const CACHE_KEY = 'feature_flags.rules.v1';

    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly FeatureFlagRepositoryInterface $flags,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly CacheRepository $cache,
        private readonly AppConfigCache $configCache,
    ) {}

    public function isEnabled(FeatureFlagKey $key, ?FlagContext $context = null): bool
    {
        $rule = $this->rules()[$key->value] ?? null;

        return $rule === null ? $this->defaultFor($key) : self::evaluate($key, $rule, $context ?? new FlagContext);
    }

    /**
     * Pure evaluation of one stored rule (also used by tests and by the documented client algorithm).
     *
     * @param array{enabled: bool, apps: list<string>, city_ids: list<int>, rollout_percent: int} $rule
     */
    public static function evaluate(FeatureFlagKey $key, array $rule, FlagContext $context): bool
    {
        if (! $rule['enabled']) {
            return false;
        }

        if ($rule['apps'] !== [] && ($context->app === null || ! in_array($context->app->value, $rule['apps'], true))) {
            return false;
        }

        if ($rule['city_ids'] !== [] && ($context->cityId === null || ! in_array($context->cityId, $rule['city_ids'], true))) {
            return false;
        }

        if ($rule['rollout_percent'] >= 100) {
            return true;
        }

        if ($rule['rollout_percent'] <= 0 || $context->subject === null || $context->subject === '') {
            return false;
        }

        return self::bucket($key, $context->subject) < $rule['rollout_percent'];
    }

    public static function bucket(FeatureFlagKey $key, string $subject): int
    {
        return crc32($key->value.'|'.$subject) % 100;
    }

    /**
     * Master switch per key (the stored switch, or the default when no row exists), as served in `features`.
     *
     * @return array<string, bool>
     */
    public function masterSwitches(): array
    {
        $rules = $this->rules();
        $out = [];

        foreach (FeatureFlagKey::cases() as $key) {
            $out[$key->value] = isset($rules[$key->value]) ? $rules[$key->value]['enabled'] : $this->defaultFor($key);
        }

        return $out;
    }

    /**
     * Targeting rules served in `flags` for client-side evaluation. Keys without a row are omitted (the master switch is the answer).
     *
     * @return array<string, array{apps: list<string>, city_ids: list<int>, rollout_percent: int}>
     */
    public function publicRules(): array
    {
        $out = [];

        foreach ($this->rules() as $key => $rule) {
            $out[$key] = ['apps' => $rule['apps'], 'city_ids' => $rule['city_ids'], 'rollout_percent' => $rule['rollout_percent']];
        }

        return $out;
    }

    /**
     * Creates the row for the key or updates it.
     *
     * @throws ConflictException `stale_version`
     */
    public function save(Admin $actor, FeatureFlagData $data): FeatureFlag
    {
        $flag = $this->db->transaction(function () use ($actor, $data): FeatureFlag {
            $existing = $this->flags->findByKey($data->key->value);
            $attributes = [
                'key' => $data->key,
                'enabled' => $data->enabled,
                'apps' => $data->apps === [] ? null : $data->apps,
                'city_ids' => $data->cityIds === [] ? null : $data->cityIds,
                'rollout_percent' => $data->rolloutPercent,
                'note' => $data->note,
            ];

            if (! $existing instanceof FeatureFlag) {
                $flag = $this->flags->create($attributes);
                $this->audit->record(AuditAction::Created, $flag, $actor, after: $flag->auditSnapshot());

                return $flag;
            }

            $locked = $this->flags->lockById($existing->id) ?? throw (new ModelNotFoundException)->setModel(FeatureFlag::class);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->flags->update($locked, $attributes + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::Updated, $locked, $actor, before: $before, after: $locked->auditSnapshot());

            return $locked;
        });

        $this->forget();

        return $flag;
    }

    public function forget(): void
    {
        $this->cache->forget(self::CACHE_KEY);
        $this->configCache->forget();
    }

    private function defaultFor(FeatureFlagKey $key): bool
    {
        $setting = $key->legacySetting();

        return $setting !== null && $this->settings->bool($setting);
    }

    /**
     * @return array<string, array{enabled: bool, apps: list<string>, city_ids: list<int>, rollout_percent: int}>
     */
    private function rules(): array
    {
        /** @var array<string, array{enabled: bool, apps: list<string>, city_ids: list<int>, rollout_percent: int}> $rules */
        $rules = $this->cache->remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): array => $this->flags->allKeyed()
            ->map(static fn (FeatureFlag $flag): array => [
                'enabled' => $flag->enabled,
                'apps' => array_values($flag->apps ?? []),
                'city_ids' => array_values(array_map('intval', $flag->city_ids ?? [])),
                'rollout_percent' => $flag->rollout_percent,
            ])->all());

        return $rules;
    }
}
