<?php

namespace App\DTOs\Markets;

use App\Enums\Common\Locale;
use App\Http\Requests\Admin\Markets\StoreCountryRequest;
use App\Http\Requests\Admin\Markets\UpdateCountryRequest;
use App\Models\Country;

/**
 * Validated input for creating/updating a country.
 */
final readonly class CountryData
{
    /**
     * @param array<string, string|null> $name translations keyed by locale
     */
    public function __construct(
        public string $iso2,
        public array $name,
        public string $phoneCode,
        public string $currencyCode,
        public string $timezone,
        public Locale $defaultLocale,
        public bool $active,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCountryRequest|UpdateCountryRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $name = [];

        foreach ((array) ($data['name'] ?? []) as $locale => $value) {
            $name[(string) $locale] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        $country = $request->route('country');

        return new self(
            iso2: strtoupper(trim((string) ($data['iso2'] ?? ($country instanceof Country ? $country->iso2 : '')))),
            name: $name,
            phoneCode: trim((string) $data['phone_code']),
            currencyCode: strtoupper(trim((string) $data['currency_code'])),
            timezone: (string) $data['timezone'],
            defaultLocale: Locale::from((string) $data['default_locale']),
            active: (bool) ($data['active'] ?? false),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * Model attributes (lock_version is managed by the service).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'iso2' => $this->iso2,
            'name' => array_filter($this->name, static fn (?string $value): bool => $value !== null),
            'phone_code' => $this->phoneCode,
            'currency_code' => $this->currencyCode,
            'timezone' => $this->timezone,
            'default_locale' => $this->defaultLocale->value,
            'active' => $this->active,
        ];
    }
}
