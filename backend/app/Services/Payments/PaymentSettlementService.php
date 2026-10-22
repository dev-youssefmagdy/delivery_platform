<?php

namespace App\Services\Payments;

use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Payments\PaymentActor;
use App\Enums\Common\ActorType;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Finance\BookingSettlementService;
use App\Services\Finance\LedgerAccountResolver;
use App\Services\Finance\LedgerService;
use App\Services\Finance\SettlementPostingBuilder;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * Money side of a payment that the gateway confirmed (called only after a verified webhook or query made it `paid`):
 * posts the ledger entry from the stored price breakdown with SettlementPostingBuilder::gateway (trip) or ::gatewayTip
 * (tip), in the same transaction as the status change. Idempotent by `payment-settlement:<uuid>`.
 *
 * Trip: Dr gateway_clearing (collected) / Cr provider_wallet, platform_revenue, taxes_payable (and promo expense for a
 * platform-funded coupon). Tip: Dr gateway_clearing / Cr provider_wallet (commission-free unless the rule says so).
 */
final class PaymentSettlementService
{
    public function __construct(
        private readonly SettlementPostingBuilder $postings,
        private readonly LedgerService $ledger,
        private readonly LedgerAccountResolver $accounts,
        private readonly BookingSettlementService $bookingSettlement,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentTransitionService $transitions,
        private readonly CurrencyRegistry $currencies,
    ) {}

    public function settle(Payment $payment): Payment
    {
        if ($payment->settled_at !== null) {
            return $payment;
        }

        $booking = $this->bookings->find($payment->booking_id);

        if (! $booking instanceof Booking) {
            throw new LogicException('A payment needs its booking to be settled.');
        }

        $booking->loadMissing('provider');
        $provider = $booking->provider;

        if (! $provider instanceof ProviderProfile) {
            throw new LogicException('A paid booking needs its provider to be settled.');
        }

        $currency = $this->currencies->get($payment->currency);
        $accounts = $this->accounts->settlementAccounts($provider, $payment->currency);

        if ($payment->kind === PaymentKind::Tip) {
            $breakdown = $this->bookingSettlement->breakdownFor($booking, null, Money::of($payment->amount_minor, $currency));
            $lines = $this->postings->gatewayTip($breakdown, $accounts);
            $type = JournalEntryType::Tip;
            $description = 'ledger.entry.tip';
            $meta = ['booking' => $booking->code, 'tip_minor' => $payment->amount_minor, 'tip_commission_minor' => $breakdown->tipCommission->amount];
        } else {
            $breakdown = PriceBreakdown::fromArray((array) $booking->price_breakdown, $currency);
            $lines = $this->postings->gateway($breakdown, $accounts);
            $type = JournalEntryType::BookingSettlementGateway;
            $description = 'ledger.entry.booking_settlement_gateway';
            $meta = ['booking' => $booking->code, 'commission_minor' => $breakdown->totalCommission()->amount, 'customer_payable_minor' => $breakdown->customerPayable->amount];
        }

        $entryId = null;

        if ($lines !== []) {
            $result = $this->ledger->post(new JournalEntryData(
                type: $type,
                idempotencyKey: 'payment-settlement:'.$payment->uuid,
                descriptionKey: $description,
                lines: $lines,
                referenceType: 'booking',
                referenceId: $booking->id,
                meta: $meta + ['payment' => $payment->uuid],
                actorType: ActorType::System,
            ));
            $entryId = $result->entry->id;
        }

        $now = CarbonImmutable::now();
        $this->payments->update($payment, ['settled_at' => $now, 'settlement_entry_id' => $entryId]);
        $this->transitions->note($payment, PaymentEventType::Settled, PaymentActor::system(), ['entry_id' => $entryId]);

        if ($payment->kind === PaymentKind::Trip && $booking->settled_at === null) {
            $this->bookings->forceUpdate($booking, ['settled_at' => $now]);
        }

        return $payment;
    }
}
