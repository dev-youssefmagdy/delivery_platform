<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CommissionTerms;
use App\DTOs\Finance\DiscountFundingSplit;
use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Finance\PriceBreakdownInput;
use App\Enums\Common\ActorType;
use App\Enums\Finance\DiscountFunding;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Settings\SettingKey;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * Money side of a finished trip (Plan P3.1 step 5, SRS §12.4), called from the completion path inside the booking's
 * transaction with the booking row locked.
 *
 * 1. The price breakdown is produced once by PriceBreakdownCalculator from the agreed fare, the booking's commission
 *    snapshot (taken at selection) and the tax / fee settings, and stored on the booking. A booking that already has a
 *    breakdown keeps it: the snapshot never changes (Plan I4).
 * 2. A cash booking is settled at once: the driver holds the cash, so the ledger records the platform's share as
 *    commission owed by the provider (Plan §7.4). The entry is idempotent by `booking-settlement:<uuid>`.
 *    Card and wallet bookings are settled by the payment flow when the gateway confirms the payment (P3.2); the
 *    breakdown is stored for them now so the receipt and the later posting use the same numbers.
 *
 * Add-ons, coupons and tips are zero until their milestones fill the corresponding input (P4, P3.3, P3.2).
 */
final class BookingSettlementService
{
    public function __construct(
        private readonly PriceBreakdownCalculator $calculator,
        private readonly SettlementPostingBuilder $postings,
        private readonly LedgerService $ledger,
        private readonly LedgerAccountResolver $accounts,
        private readonly CommissionSnapshotService $commissions,
        private readonly BookingRepositoryInterface $bookings,
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
    ) {}

    public function onCompleted(Booking $booking): Booking
    {
        $booking = $this->withBreakdown($booking);

        if ($booking->payment_method === PaymentMethodType::Cash) {
            $booking = $this->settleCash($booking);
        }

        return $booking;
    }

    /**
     * The breakdown of a booking as it will be (or was) stored. Pure with respect to the booking: nothing is written.
     */
    public function breakdownFor(Booking $booking, ?CommissionTerms $terms = null): PriceBreakdown
    {
        $currency = $this->currencies->get($booking->currency);
        $fare = $booking->final_fare_minor ?? $booking->agreed_fare_minor ?? throw new LogicException('A booking needs an agreed fare to be priced.');

        return $this->calculator->calculate(new PriceBreakdownInput(
            agreedFare: Money::of($fare, $currency),
            addons: Money::zero($currency),
            discount: Money::zero($currency),
            funding: DiscountFundingSplit::of(DiscountFunding::Platform),
            customerFee: Money::of($this->settings->int(SettingKey::CustomerServiceFeeMinor), $currency),
            taxRateBp: $this->settings->int(SettingKey::TaxRateBasisPoints),
            tip: Money::zero($currency),
            commission: $terms ?? $this->termsOf($booking),
        ));
    }

    private function withBreakdown(Booking $booking): Booking
    {
        if ($booking->price_breakdown !== null) {
            return $booking;
        }

        $attributes = [];

        // A booking selected before the money core existed has no snapshot: bind it now, and say so in the snapshot.
        if ($booking->commission_snapshot === null) {
            $attributes = $this->commissions->attributesFor($booking, CarbonImmutable::now());
        }

        $terms = isset($attributes['commission_snapshot']) ? CommissionTerms::fromSnapshot($attributes['commission_snapshot']) : null;
        $breakdown = $this->breakdownFor($booking, $terms);

        return $this->bookings->forceUpdate($booking, $attributes + ['price_breakdown' => $breakdown->toArray()]);
    }

    private function settleCash(Booking $booking): Booking
    {
        $booking->loadMissing('provider');
        $provider = $booking->provider;

        if (! $provider instanceof ProviderProfile) {
            throw new LogicException('A completed booking needs its provider to be settled.');
        }

        $breakdown = PriceBreakdown::fromArray((array) $booking->price_breakdown, $this->currencies->get($booking->currency));
        $lines = $this->postings->cash($breakdown, $this->accounts->settlementAccounts($provider, $booking->currency));

        if ($lines !== []) {
            $this->ledger->post(new JournalEntryData(
                type: JournalEntryType::BookingSettlementCash,
                idempotencyKey: 'booking-settlement:'.$booking->uuid,
                descriptionKey: 'ledger.entry.booking_settlement_cash',
                lines: $lines,
                referenceType: 'booking',
                referenceId: $booking->id,
                meta: [
                    'booking' => $booking->code,
                    'commission_minor' => $breakdown->totalCommission()->amount,
                    'customer_payable_minor' => $breakdown->customerPayable->amount,
                ],
                actorType: ActorType::System,
            ));
        }

        return $booking->settled_at === null ? $this->bookings->forceUpdate($booking, ['settled_at' => CarbonImmutable::now()]) : $booking;
    }

    private function termsOf(Booking $booking): CommissionTerms
    {
        return $booking->commission_snapshot !== null
            ? CommissionTerms::fromSnapshot($booking->commission_snapshot)
            : $this->commissions->termsFor($booking, CarbonImmutable::now());
    }
}
