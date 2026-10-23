<?php

namespace App\Services\Safety;

use App\Enums\Settings\SettingKey;
use App\Services\Settings\SettingsService;

/**
 * Local emergency numbers (`config/safety.php`, by market country) with localized names. The platform only DISPLAYS them: it
 * never calls them and never dispatches anybody (SRS §13.2).
 */
final class EmergencyNumbers
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Keys and numbers without labels (safe to cache: the apps name the services in their own language).
     *
     * @return list<array{key: string, number: string}>
     */
    public function raw(?string $iso2 = null): array
    {
        return array_map(static fn (array $entry): array => ['key' => $entry['key'], 'number' => $entry['number']], $this->forMarket($iso2));
    }

    /**
     * @return list<array{key: string, label: string, number: string}>
     */
    public function forMarket(?string $iso2 = null): array
    {
        $iso2 ??= (string) $this->settings->string(SettingKey::DefaultCountry);
        $all = (array) config('safety.emergency_numbers', []);
        $entries = (array) ($all[strtoupper($iso2)] ?? $all['default'] ?? []);

        return array_values(array_map(static fn (array $entry): array => [
            'key' => (string) $entry['key'],
            'label' => (string) __('safety.emergency.'.$entry['key']),
            'number' => (string) $entry['number'],
        ], $entries));
    }
}
