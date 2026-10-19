<?php

namespace App\Services\Settings;

use App\DTOs\Settings\SettingFieldData;
use App\DTOs\Settings\SettingsData;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingGroup;
use App\Enums\Settings\SettingInput;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Contracts\Settings\SettingsRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Config\AppConfigCache;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Typed, cached platform settings (ADR-10). Reads fall back to SettingKey::default(); writes validate against
 * SettingKey::rules(), run in a transaction under row locks, write one audit row per changed key with
 * before/after, and bust the settings cache and the public app-config cache.
 */
final class SettingsService
{
    public const CACHE_KEY = 'platform_settings.v1';

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly CountryRepositoryInterface $countries,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly CacheRepository $cache,
        private readonly AppConfigCache $appConfig,
    ) {}

    public function get(SettingKey $key): mixed
    {
        $stored = $this->stored();

        return array_key_exists($key->value, $stored) ? $key->fromStored($stored[$key->value]) : $key->default();
    }

    public function string(SettingKey $key): ?string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : null;
    }

    public function int(SettingKey $key): int
    {
        return (int) $this->get($key);
    }

    public function bool(SettingKey $key): bool
    {
        return (bool) $this->get($key);
    }

    /**
     * @return list<mixed>
     */
    public function list(SettingKey $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * Effective values of a group (stored override or default).
     *
     * @return array<string, mixed> SettingKey value => typed value
     */
    public function group(SettingGroup $group): array
    {
        $values = [];

        foreach ($group->keys() as $key) {
            $values[$key->value] = $this->get($key);
        }

        return $values;
    }

    /**
     * Fields of one settings tab with their current values and options.
     *
     * @return list<SettingFieldData>
     */
    public function fieldsFor(SettingGroup $group): array
    {
        $fields = [];

        foreach ($group->keys() as $key) {
            $unit = $key->unit();

            $fields[] = new SettingFieldData(
                key: $key,
                name: "settings[{$key->value}]".(in_array($key->input(), [SettingInput::MultiSelect], true) ? '[]' : ''),
                label: $key->label(),
                description: $key->description(),
                input: $key->input(),
                value: $this->get($key),
                options: $this->optionsFor($key),
                bounds: $key->bounds(),
                unit: $unit === null ? null : (string) __("admin.settings.units.{$unit}"),
                required: ! $key->isNullable(),
            );
        }

        return $fields;
    }

    /**
     * Opaque token of the current values of a group; the form sends it back so concurrent edits are detected.
     */
    public function fingerprint(SettingGroup $group): string
    {
        return sha1((string) json_encode($this->group($group)));
    }

    /**
     * Saves one tab. Returns the keys that actually changed (an unchanged save is a no-op).
     *
     * @return list<SettingKey>
     */
    public function updateGroup(SettingsData $data, Admin $actor, ?string $reason = null): array
    {
        $changed = $this->db->transaction(function () use ($data, $actor, $reason): array {
            $keys = $data->group->keys();
            $this->settings->lockByKeys(array_map(static fn (SettingKey $key): string => $key->value, $keys));
            $this->flush();

            if ($data->fingerprint !== null && ! hash_equals($this->fingerprint($data->group), $data->fingerprint)) {
                throw ConflictException::make('stale_version');
            }

            $new = [];

            foreach ($keys as $key) {
                $new[$key->value] = $this->validated($key, $data->values[$key->value] ?? $key->fromInput(null));
            }

            $this->assertConsistent($new);

            $changed = [];

            foreach ($keys as $key) {
                $before = $this->get($key);

                if ($this->same($before, $new[$key->value])) {
                    continue;
                }

                $row = $this->settings->put($key->value, $new[$key->value], (int) $actor->getKey());
                $this->audit->record(
                    AuditAction::Updated,
                    $row,
                    $actor,
                    ['key' => $key->value, 'value' => $before],
                    ['key' => $key->value, 'value' => $new[$key->value]],
                    $reason,
                    ['group' => $data->group->value],
                );
                $changed[] = $key;
            }

            $this->flush();

            return $changed;
        });

        $this->flush();

        return $changed;
    }

    /**
     * Sets one key programmatically (seeders, commands, tests). Validated and audited like the screen.
     */
    public function set(SettingKey $key, mixed $value, ?Authenticatable $actor = null, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($key, $value, $actor, $reason): void {
            $this->settings->lockByKeys([$key->value]);
            $this->flush();

            $new = $this->validated($key, $key->fromInput($value));
            $this->assertConsistent([$key->value => $new]);
            $before = $this->get($key);

            if ($this->same($before, $new)) {
                return;
            }

            $row = $this->settings->put($key->value, $new, $actor instanceof Admin ? (int) $actor->getKey() : null);
            $this->audit->record(AuditAction::Updated, $row, $actor, ['key' => $key->value, 'value' => $before], ['key' => $key->value, 'value' => $new], $reason, ['group' => $key->group()->value]);
            $this->flush();
        });

        $this->flush();
    }

    /**
     * Drops every cached copy (settings and the public app config).
     */
    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
        $this->appConfig->forget();
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        /** @var array<string, mixed> $stored */
        $stored = $this->cache->rememberForever(self::CACHE_KEY, fn (): array => $this->settings->storedValues());

        return $stored;
    }

    /**
     * @return array<string, string>
     */
    private function optionsFor(SettingKey $key): array
    {
        if ($key === SettingKey::DefaultCountry) {
            $options = [];

            foreach ($this->countries->active() as $country) {
                $options[$country->iso2] = $country->name.' ('.$country->iso2.')';
            }

            return $options;
        }

        if ($key === SettingKey::DefaultCurrency) {
            $options = [];

            foreach ($this->currencies->active() as $currency) {
                $options[$currency->code] = $currency->optionLabel();
            }

            return $options;
        }

        return $key->staticOptions() ?? [];
    }

    private function validated(SettingKey $key, mixed $value): mixed
    {
        $rules = ['value' => $key->rules()];

        if ($key->itemRules() !== []) {
            $rules['value.*'] = $key->itemRules();
        }

        $validator = Validator::make(['value' => $value], $rules, [], ['value' => $key->label()]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(["settings.{$key->value}" => $validator->errors()->get('value')]);
        }

        return $key->fromInput($value);
    }

    /**
     * Cross-field invariants. Keys missing from $new are read from the current effective values.
     *
     * @param array<string, mixed> $new
     */
    private function assertConsistent(array $new): void
    {
        $value = fn (SettingKey $key): mixed => array_key_exists($key->value, $new) ? $new[$key->value] : $this->get($key);
        $touches = static fn (SettingKey ...$keys): bool => array_intersect(array_map(static fn (SettingKey $key): string => $key->value, $keys), array_keys($new)) !== [];

        if ($touches(SettingKey::DefaultLocale, SettingKey::SupportedLocales) && ! in_array($value(SettingKey::DefaultLocale), (array) $value(SettingKey::SupportedLocales), true)) {
            throw BusinessRuleViolation::make('setting_default_locale_unsupported', 'settings.'.SettingKey::DefaultLocale->value);
        }

        if ($touches(SettingKey::OtpResendCooldownSeconds, SettingKey::OtpTtlSeconds) && (int) $value(SettingKey::OtpResendCooldownSeconds) >= (int) $value(SettingKey::OtpTtlSeconds)) {
            throw BusinessRuleViolation::make('setting_otp_cooldown_exceeds_ttl', 'settings.'.SettingKey::OtpResendCooldownSeconds->value);
        }

        if ($touches(SettingKey::OfferTtlSeconds, SettingKey::BookingSearchTtlSeconds) && (int) $value(SettingKey::OfferTtlSeconds) > (int) $value(SettingKey::BookingSearchTtlSeconds)) {
            throw BusinessRuleViolation::make('setting_offer_ttl_exceeds_search_ttl', 'settings.'.SettingKey::OfferTtlSeconds->value);
        }

        if ($touches(SettingKey::DispatchRadiusStepsKm)) {
            $steps = array_values((array) $value(SettingKey::DispatchRadiusStepsKm));
            $sorted = $steps;
            sort($sorted);

            if ($steps !== $sorted || count(array_unique($steps)) !== count($steps)) {
                throw BusinessRuleViolation::make('setting_radius_steps_not_ascending', 'settings.'.SettingKey::DispatchRadiusStepsKm->value);
            }
        }
    }

    /**
     * Value equality that treats 2 and 2.0 as equal inside numeric lists.
     */
    private function same(mixed $a, mixed $b): bool
    {
        if (is_array($a) && is_array($b)) {
            return array_map(static fn (mixed $item): mixed => is_int($item) ? (float) $item : $item, $a)
                === array_map(static fn (mixed $item): mixed => is_int($item) ? (float) $item : $item, $b);
        }

        return $a === $b;
    }
}
