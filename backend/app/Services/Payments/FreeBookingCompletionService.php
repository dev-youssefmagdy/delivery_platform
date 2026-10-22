<?php

namespace App\Services\Payments;

use App\DTOs\Booking\TransitionContext;
use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Payments\PaymentActor;
use App\Enums\Booking\BookingStatus;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Booking\BookingTransitionService;
use App\Support\Money\CurrencyRegistry;
use Illuminate\Support\Str;

/**
 * A card / wallet booking whose customer payable is fully covered by a coupon (a 100% discount, tax 0) has nothing to charge:
 * no gateway intent is created (it would answer `payment_nothing_to_pay`). Inside the completion transaction this service
 *
 * 1. opens the trip payment as `not_required` with amount 0 (the booking's payment status follows through the payment
 *    transition service),
 * 2. settles it through the ledger exactly like a paid trip (PaymentSettlementService: the platform-funded discount posts to
 *    promo_expense, the provider is credited gross minus commission minus any provider-funded part), and
 * 3. moves the booking `completed_pending_payment -> completed` through BookingTransitionService.
 *
 * Idempotent: a booking that already has its trip payment is only moved to completed.
 */
final class FreeBookingCompletionService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentTransitionService $transitions,
        private readonly PaymentSettlementService $settlement,
        private readonly BookingTransitionService $bookingTransitions,
        private readonly CurrencyRegistry $currencies,
    ) {}

    public function completeIfNothingToPay(Booking $booking): Booking
    {
        if ($booking->payment_method === PaymentMethodType::Cash || $booking->status !== BookingStatus::CompletedPendingPayment || $booking->price_breakdown === null) {
            return $booking;
        }

        $breakdown = PriceBreakdown::fromArray((array) $booking->price_breakdown, $this->currencies->get($booking->currency));

        if ($breakdown->customerPayable->subtract($breakdown->tip)->isPositive()) {
            return $booking;
        }

        $payment = $this->payments->forBooking($booking->id, PaymentKind::Trip)->first();

        if (! $payment instanceof Payment) {
            $payment = $this->payments->insert([
                'uuid' => (string) Str::uuid(),
                'booking_id' => $booking->id,
                'user_id' => $booking->customer_id,
                'provider_id' => $booking->provider_id,
                'kind' => PaymentKind::Trip,
                'method_type' => $booking->payment_method,
                'gateway' => null,
                'amount_minor' => 0,
                'currency' => $booking->currency,
                'status' => PaymentStatus::NotRequired,
                'paid_at' => now(),
                'meta' => ['reason' => 'nothing_to_pay'],
            ]);
            $this->transitions->opened($payment, PaymentEventType::StatusChanged, PaymentActor::system(), ['reason' => 'nothing_to_pay']);
        }

        $this->settlement->settle($payment);

        return $this->bookingTransitions->transition($booking, BookingStatus::Completed, TransitionContext::system('nothing_to_pay', ['payment' => $payment->uuid]));
    }
}
