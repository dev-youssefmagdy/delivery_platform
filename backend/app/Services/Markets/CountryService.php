<?php

namespace App\Services\Markets;

use App\DTOs\Markets\CountryData;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Country;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use App\Support\Config\AppConfigCache;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Country business rules: transactional audited writes, optimistic locking; the ISO code is immutable, the
 * currency must be active, and the platform's default country can be neither deactivated nor deleted.
 */
final class CountryService
{
    public function __construct(
        private readonly CountryRepositoryInterface $countries,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly AppConfigCache $appConfig,
    ) {}

    public function create(CountryData $data, ?Authenticatable $actor): Country
    {
        if ($this->countries->isoExists($data->iso2)) {
            throw BusinessRuleViolation::make('country_iso_taken', 'iso2');
        }

        $this->assertCurrencyUsable($data);

        try {
            $country = $this->db->transaction(function () use ($data, $actor): Country {
                /** @var Country $country */
                $country = $this->countries->create($data->attributes());
                $this->audit->record(AuditAction::Created, $country, $actor, after: $country->auditSnapshot());

                return $country;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('country_iso_taken', 'iso2');
        }

        $this->appConfig->forget();

        return $country;
    }

    public function update(Country $country, CountryData $data, ?Authenticatable $actor): Country
    {
        $updated = $this->db->transaction(function () use ($country, $data, $actor): Country {
            $locked = $this->lock($country);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($data->iso2 !== $locked->iso2) {
                throw BusinessRuleViolation::make('country_iso_immutable', 'iso2');
            }

            if ($locked->active && ! $data->active) {
                $this->assertNotDefault($locked, 'active');
            }

            $this->assertCurrencyUsable($data);

            $before = $locked->auditSnapshot();
            $this->countries->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });

        $this->appConfig->forget();

        return $updated;
    }

    public function delete(Country $country, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($country, $actor, $reason): void {
            $locked = $this->lock($country);
            $this->assertNotDefault($locked);

            $before = $locked->auditSnapshot();
            $this->countries->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });

        $this->appConfig->forget();
    }

    /**
     * Active currencies as select options (code => label).
     *
     * @return array<string, string>
     */
    public function currencyOptions(): array
    {
        $options = [];

        foreach ($this->currencies->active() as $currency) {
            $options[$currency->code] = $currency->optionLabel();
        }

        return $options;
    }

    private function lock(Country $country): Country
    {
        $locked = $this->countries->lockById($country->getKey());

        return $locked instanceof Country ? $locked : throw (new ModelNotFoundException)->setModel(Country::class);
    }

    private function assertNotDefault(Country $country, string $field = 'iso2'): void
    {
        if ($this->settings->string(SettingKey::DefaultCountry) === $country->iso2) {
            throw BusinessRuleViolation::make('country_is_default', $field);
        }
    }

    private function assertCurrencyUsable(CountryData $data): void
    {
        $currency = $this->currencies->findByCode($data->currencyCode);

        if ($currency === null || (! $currency->active && $data->active)) {
            throw BusinessRuleViolation::make('country_currency_unusable', 'currency_code');
        }
    }
}
