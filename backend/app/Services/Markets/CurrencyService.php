<?php

namespace App\Services\Markets;

use App\DTOs\Markets\CurrencyData;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Currency;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use App\Support\Config\AppConfigCache;
use App\Support\Money\CurrencyRegistry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Currency business rules: transactional audited writes, optimistic locking, and the integrity rules of money
 * (ADR-04): the code and minor-unit exponent never change after creation, and a currency that is the default
 * or used by a country can be neither deactivated nor deleted.
 */
final class CurrencyService
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly CountryRepositoryInterface $countries,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly CurrencyRegistry $registry,
        private readonly AppConfigCache $appConfig,
    ) {}

    public function create(CurrencyData $data, ?Authenticatable $actor): Currency
    {
        if ($this->currencies->codeExists($data->code)) {
            throw BusinessRuleViolation::make('currency_code_taken', 'code');
        }

        try {
            $currency = $this->db->transaction(function () use ($data, $actor): Currency {
                /** @var Currency $currency */
                $currency = $this->currencies->create($data->attributes());
                $this->audit->record(AuditAction::Created, $currency, $actor, after: $currency->auditSnapshot());

                return $currency;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('currency_code_taken', 'code');
        }

        $this->changed();

        return $currency;
    }

    public function update(Currency $currency, CurrencyData $data, ?Authenticatable $actor): Currency
    {
        $updated = $this->db->transaction(function () use ($currency, $data, $actor): Currency {
            $locked = $this->lock($currency);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($data->code !== $locked->code) {
                throw BusinessRuleViolation::make('currency_code_immutable', 'code');
            }

            if ($data->exponent !== $locked->exponent) {
                throw BusinessRuleViolation::make('currency_exponent_immutable', 'exponent');
            }

            if ($locked->active && ! $data->active) {
                $this->assertNotInUse($locked, 'active');
            }

            $before = $locked->auditSnapshot();
            $this->currencies->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });

        $this->changed();

        return $updated;
    }

    public function delete(Currency $currency, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($currency, $actor, $reason): void {
            $locked = $this->lock($currency);
            $this->assertNotInUse($locked);

            $before = $locked->auditSnapshot();
            $this->currencies->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });

        $this->changed();
    }

    private function lock(Currency $currency): Currency
    {
        $locked = $this->currencies->lockById($currency->getKey());

        return $locked instanceof Currency ? $locked : throw (new ModelNotFoundException)->setModel(Currency::class);
    }

    private function assertNotInUse(Currency $currency, string $field = 'code'): void
    {
        if ($this->settings->string(SettingKey::DefaultCurrency) === $currency->code || $this->countries->countUsingCurrency($currency->code) > 0) {
            throw BusinessRuleViolation::make('currency_in_use', $field);
        }
    }

    private function changed(): void
    {
        $this->registry->flush();
        $this->appConfig->forget();
    }
}
