<?php

namespace App\Services\Booking;

use App\Enums\Common\ActorType;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Abuse cooldowns and unpaid cancellation fees (SRS §8.4, Plan P3.5). Computed from the bookings themselves, so nothing
 * is stored that could drift: a party that reaches `cancellation_abuse_threshold` counted cancellations / no-shows
 * inside `cancellation_abuse_window_hours` is paused until `cancellation_cooldown_minutes` after the latest one.
 * A customer cannot book while the cooldown runs or while unpaid cancellation fees (a negative wallet) exceed
 * `customer_fee_debt_limit_minor`; a provider in cooldown is not eligible to work (ProviderEligibilityService).
 */
final class CancellationAbuseService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly SettingsService $settings,
    ) {}

    /**
     * The moment a party may act again, or null when no cooldown applies now.
     */
    public function cooldownUntil(ActorType $party, int $partyId, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        $now ??= CarbonImmutable::now();
        $window = $this->settings->int(SettingKey::CancellationAbuseWindowHours);
        $signals = $this->bookings->abuseSignalTimes($party, $partyId, $now->subHours($window));
        $threshold = $this->settings->int(SettingKey::CancellationAbuseThreshold);

        if (count($signals) < $threshold) {
            return null;
        }

        $until = end($signals)->addMinutes($this->settings->int(SettingKey::CancellationCooldownMinutes));

        return $until->greaterThan($now) ? $until : null;
    }

    public function providerCooldownUntil(ProviderProfile $provider, ?CarbonImmutable $now = null): ?CarbonImmutable
    {
        return $this->cooldownUntil(ActorType::Provider, $provider->id, $now);
    }

    /**
     * Unpaid cancellation fees of a customer in minor units (the negative part of their wallets).
     */
    public function customerDebtMinor(User $customer): int
    {
        return (int) $this->accounts->forOwner(LedgerAccountType::CustomerWallet, $customer->id)
            ->sum(static fn (LedgerAccount $account): int => max(0, -$account->balance_minor));
    }

    /**
     * The wallet that is in debt (the most negative one), or null when the customer owes nothing.
     */
    public function debtAccount(User $customer): ?LedgerAccount
    {
        return $this->accounts->forOwner(LedgerAccountType::CustomerWallet, $customer->id)
            ->filter(static fn (LedgerAccount $account): bool => $account->balance_minor < 0)
            ->sortBy('balance_minor')->first();
    }

    /**
     * @throws BusinessRuleViolation `cancellation_cooldown_active`, `customer_fee_debt_outstanding`
     */
    public function assertCustomerMayBook(User $customer): void
    {
        $until = $this->cooldownUntil(ActorType::Customer, $customer->id);

        if ($until !== null) {
            throw BusinessRuleViolation::make('cancellation_cooldown_active', context: ['until' => $until->toIso8601ZuluString(), 'minutes' => (int) ceil(CarbonImmutable::now()->diffInSeconds($until) / 60)]);
        }

        $debt = $this->customerDebtMinor($customer);

        if ($debt > 0 && $debt > $this->settings->int(SettingKey::CustomerFeeDebtLimitMinor)) {
            throw BusinessRuleViolation::make('customer_fee_debt_outstanding', context: ['debt_minor' => $debt]);
        }
    }
}
