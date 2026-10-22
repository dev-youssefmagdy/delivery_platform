<?php

namespace App\Services\Config;

use App\DTOs\Config\AppConfigData;
use App\Enums\Common\Locale;
use App\Enums\Settings\DigitsPreference;
use App\Enums\Settings\SettingKey;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Config\AppConfigCache;

/**
 * Assembles the public app configuration (SRS §3, §16.15) from typed settings, markets and current policy
 * versions. Cached; the services that change any input bust the cache (AppConfigCache::forget()).
 */
final class AppConfigService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly CountryRepositoryInterface $countries,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly PolicyVersionRepositoryInterface $policies,
        private readonly AppConfigCache $cache,
    ) {}

    public function get(): AppConfigData
    {
        return AppConfigData::fromArray($this->cache->remember(fn (): array => $this->build()->toArray()));
    }

    /**
     * Supported locales in settings order, restricted to locales the platform knows.
     *
     * @return list<Locale>
     */
    public function supportedLocales(): array
    {
        $codes = array_map('strval', $this->settings->list(SettingKey::SupportedLocales));
        $locales = array_values(array_filter(array_map(Locale::tryFromValue(...), $codes)));

        return $locales === [] ? [Locale::default()] : $locales;
    }

    public function defaultLocale(): Locale
    {
        $default = Locale::tryFromValue($this->settings->string(SettingKey::DefaultLocale)) ?? Locale::default();

        return in_array($default, $this->supportedLocales(), true) ? $default : $this->supportedLocales()[0];
    }

    private function build(): AppConfigData
    {
        $country = $this->countries->findByIso2((string) $this->settings->string(SettingKey::DefaultCountry));
        $currency = $this->currencies->findByCode((string) $this->settings->string(SettingKey::DefaultCurrency));

        $policies = [];

        foreach ($this->policies->currentPublished() as $policy) {
            /** @var PolicyVersion $policy */
            $policies[$policy->type->value] = [
                'id' => $policy->uuid,
                'version' => (int) $policy->version,
                'published_at' => (string) $policy->published_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        return new AppConfigData(
            defaultLocale: $this->defaultLocale()->value,
            locales: array_map(static fn (Locale $locale): string => $locale->value, $this->supportedLocales()),
            country: $country === null ? null : ['iso2' => $country->iso2, 'phone_code' => $country->phone_code, 'timezone' => $country->timezone],
            currency: $currency === null ? null : [
                'code' => $currency->code,
                'exponent' => $currency->exponent,
                'symbol_ar' => $currency->symbol_ar,
                'symbol_en' => $currency->symbol_en,
            ],
            digitsPreference: (DigitsPreference::tryFromValue($this->settings->string(SettingKey::DigitsPreference)) ?? DigitsPreference::Auto)->value,
            support: [
                'phone' => $this->settings->string(SettingKey::SupportPhone),
                'email' => $this->settings->string(SettingKey::SupportEmail),
                'whatsapp' => $this->settings->string(SettingKey::SupportWhatsapp),
            ],
            maintenance: $this->settings->bool(SettingKey::MaintenanceModeApps),
            apps: [
                'customer' => [
                    'min_version' => (string) $this->settings->string(SettingKey::MinAppVersionCustomer),
                    'force_update' => $this->settings->bool(SettingKey::ForceUpdateCustomer),
                ],
                'provider' => [
                    'min_version' => (string) $this->settings->string(SettingKey::MinAppVersionDriver),
                    'force_update' => $this->settings->bool(SettingKey::ForceUpdateDriver),
                ],
            ],
            policies: $policies,
            features: [
                'wallet' => $this->settings->bool(SettingKey::FeatureWalletEnabled),
                'offers' => $this->settings->bool(SettingKey::FeatureOffersEnabled),
            ],
        );
    }
}
