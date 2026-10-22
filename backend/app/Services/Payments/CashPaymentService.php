<?php

namespace App\Services\Payments;

use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Payments\PaymentActor;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\AuditAction;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Money\CurrencyRegistry;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * The cash payment of a booking (SRS §12.1, §6.7). The payment row is opened as `cash_due` when the trip completes (same
 * transaction as the cash settlement); the provider's explicit "cash collected" confirmation - the verified cash rule -
 * moves it to `cash_collected` (the booking's payment status follows). The ledger already holds the commission owed from
 * the completion (BookingSettlementService), so the confirmation posts nothing: it closes the "cash due" state.
 */
final class CashPaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PaymentTransitionService $transitions,
        private readonly CurrencyRegistry $currencies,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Opens (once) the `cash_due` payment of a completed cash booking. Idempotent. Caller is inside the completion transaction.
     */
    public function open(Booking $booking): Payment
    {
        $existing = $this->payments->forBooking($booking->id, PaymentKind::Trip)->first();

        if ($existing instanceof Payment) {
            return $existing;
        }

        $currency = $this->currencies->get($booking->currency);
        $breakdown = PriceBreakdown::fromArray((array) $booking->price_breakdown, $currency);

        $payment = $this->payments->insert([
            'uuid' => (string) Str::uuid(),
            'booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'provider_id' => $booking->provider_id,
            'kind' => PaymentKind::Trip,
            'method_type' => PaymentMethodType::Cash,
            'gateway' => null,
            'amount_minor' => $breakdown->customerPayable->subtract($breakdown->tip)->amount,
            'currency' => $booking->currency,
            'status' => PaymentStatus::CashDue,
        ]);
        $this->transitions->opened($payment, PaymentEventType::StatusChanged, PaymentActor::system(), ['method' => 'cash']);

        return $payment;
    }

    /**
     * The provider confirms they collected the cash. A repeat confirmation is a replay, not an error.
     *
     * @return array{payment: Payment, replayed: bool}
     *
     * @throws ModelNotFoundException another provider's booking is a 404
     * @throws BusinessRuleViolation the booking is not a completed cash booking
     */
    public function confirm(ProviderProfile $provider, string $bookingUuid): array
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->db->transaction(function () use ($provider, $booking): array {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking || $locked->provider_id !== $provider->id) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if ($locked->payment_method !== PaymentMethodType::Cash || ! in_array($locked->status, [BookingStatus::Completed, BookingStatus::Disputed], true)) {
                throw BusinessRuleViolation::make('cash_not_collectable');
            }

            $payment = $this->payments->lockById($this->open($locked)->id);

            if (! $payment instanceof Payment) {
                throw (new ModelNotFoundException)->setModel(Payment::class);
            }

            if ($payment->status === PaymentStatus::CashCollected) {
                return ['payment' => $payment, 'replayed' => true];
            }

            if ($payment->status !== PaymentStatus::CashDue) {
                throw BusinessRuleViolation::make('cash_not_collectable');
            }

            $this->transitions->transition($payment, PaymentStatus::CashCollected, PaymentEventType::CashCollected, PaymentActor::provider($provider));
            $this->audit->record(AuditAction::CashCollected, $payment, null, after: ['status' => PaymentStatus::CashCollected->value, 'provider' => $provider->uuid]);

            return ['payment' => $payment, 'replayed' => false];
        });
    }
}
