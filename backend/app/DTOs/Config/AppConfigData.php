<?php

namespace App\DTOs\Config;

/**
 * Public application configuration served to the mobile apps (`GET /api/v1/config/app`). Contains no secrets.
 * It is cached as a plain array (`toArray()` / `fromArray()`), never as a serialized object.
 */
final readonly class AppConfigData
{
    /**
     * @param list<string> $locales
     * @param array{iso2: string, phone_code: string, timezone: string}|null $country
     * @param array{code: string, exponent: int, symbol_ar: string, symbol_en: string}|null $currency
     * @param array{phone: string|null, email: string|null, whatsapp: string|null} $support
     * @param array<string, array<string, mixed>> $apps per app: min_version, force_update and `platforms` (min_version, latest_version, force_update, store_url)
     * @param array<string, array{id: string, version: int, published_at: string}> $policies current published version per policy type
     * @param array<string, bool> $features master switch per flag key
     * @param array<string, array{apps: list<string>, city_ids: list<int>, rollout_percent: int}> $flags targeting rules for client-side evaluation
     * @param array{emergency_numbers: list<array{key: string, number: string}>, sos_hold_seconds: int, trusted_contacts_max: int, emergency_dispatch: bool} $safety
     */
    public function __construct(
        public string $defaultLocale,
        public array $locales,
        public ?array $country,
        public ?array $currency,
        public string $digitsPreference,
        public array $support,
        public bool $maintenance,
        public array $apps,
        public array $policies,
        public array $features,
        public array $safety = [],
        public array $flags = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(...$payload);
    }
}
