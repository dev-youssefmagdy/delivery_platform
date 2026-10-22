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
use App\Services\Coupon\CouponReservationService;
use App\Services\Payments\CashPaymentService;
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
 *    The cash payment row (`cash_due`) is opened next to it; the provider's confirmation makes it `cash_collected`.
 *    Card and wallet bookings are settled by PaymentSettlementService when the gateway confirms the payment (P3.2); the
 *    breakdown is stored for them now so the receipt and the later posting use the same numbers.
 *
 * Coupons (P3.3): the discount and its funding split come from the booking's coupon reservation, computed on the agreed
 * fare under the terms of the version the reservation was made with (CouponReservationService::discountFor), never from the
 * client. At completion the reservation is committed (a redemption and funding entries are written) with the amounts of the
 * stored breakdown; the ledger postings below already carry platform / provider / partner funding. Add-ons are zero until P4.
 * The tip input is wired
 * (P3.2): a tip is its own payment and its own entry, so the stored booking breakdown keeps tip = 0 and a tip is priced by
 * `breakdownFor($booking, null, $tip)` when its payment is settled (PaymentSettlementService).
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
        private readonly CashPaymentService $cashPayments,
        private readonly CouponReservationService $coupons,
    ) {}

    public function onCompleted(Booking $booking): Booking
    {
        $booking = $this->withBreakdown($booking);
        $this->coupons->commit($booking, PriceBreakdown::fromArray((array) $booking->price_breakdown, $this->currencies->get($booking->currency)));

        if ($booking->payment_method === PaymentMethodType::Cash) {
            $booking = $this->settleCash($booking);
            // The payment axis of a cash trip: cash_due until the provider confirms the collection (P3.2).
            $this->cashPayments->open($booking);
        }

        return $booking;
    }

    /**
     * The breakdown of a booking as it will be (or was) stored. Pure with respect to the booking: nothing is written.
     * `$tip` is the tip input of PriceBreakdownCalculator: zero at completion (a tip is chosen afterwards, never
     * preselected), the tip amount when a tip payment is settled (P3.2).
     */
    public function breakdownFor(Booking $booking, ?CommissionTerms $terms = null, ?Money $tip = null): PriceBreakdown
    {
        $currency = $this->currencies->get($booking->currency);
        $fare = $booking->final_fare_minor ?? $booking->agreed_fare_minor ?? throw new LogicException('A booking needs an agreed fare to be priced.');

        $agreed = Money::of($fare, $currency);
        $coupon = $this->coupons->discountFor($booking, $agreed);

        return $this->calculator->calculate(new PriceBreakdownInput(
            agreedFare: $agreed,
            addons: Money::zero($currency),
            discount: $coupon?->amount ?? Money::zero($currency),
            funding: $coupon?->funding ?? DiscountFundingSplit::of(DiscountFunding::Platform),
            customerFee: Money::of($this->settings->int(SettingKey::CustomerServiceFeeMinor), $currency),
            taxRateBp: $this->settings->int(SettingKey::TaxRateBasisPoints),
            tip: $tip ?? Money::zero($currency),
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
            $result = $this->ledger->post(new JournalEntryData(
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
            $this->coupons->linkSettlement($booking, $result->entry->id);
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
