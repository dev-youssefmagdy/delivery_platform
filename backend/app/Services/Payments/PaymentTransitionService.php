<?php

namespace App\Services\Payments;

use App\DTOs\Payments\PaymentActor;
use App\Enums\Notification\NotificationEvent;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentStatus;
use App\Events\Realtime\PaymentStatusChanged;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentEventRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Dispatch\EventSequencer;
use App\Services\Notification\PartyNotifier;
use Carbon\CarbonImmutable;

/**
 * The only writer of `payments.status` (SRS §12.2). It enforces the PaymentStatus graph, appends the timeline row,
 * mirrors the status of a trip payment onto `bookings.payment_status` (the booking's own `status` is NOT touched here:
 * that is BookingTransitionService's alone) and announces `payment.status_changed` after commit.
 *
 * Callers hold the payment row lock (a transaction). `paid` is reachable only from the webhook / query callers and
 * `cash_collected` only from the cash confirmation: this class does not decide who may ask, the callers' sources do, and
 * the graph keeps every other path out.
 */
final class PaymentTransitionService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentEventRepositoryInterface $events,
        private readonly BookingRepositoryInterface $bookings,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly EventSequencer $sequencer,
        private readonly PartyNotifier $notifier,
    ) {}

    /**
     * Moves a locked payment to $to. The same status again is a no-op (returns the payment untouched).
     *
     * @param array<string, mixed> $attributes extra columns written with the status (gateway_reference, failure_code ...)
     * @param array<string, mixed> $meta safe facts for the timeline row
     *
     * @throws ConflictException `payment_transition_invalid`
     */
    public function transition(
        Payment $payment,
        PaymentStatus $to,
        PaymentEventType $eventType,
        PaymentActor $actor,
        array $attributes = [],
        array $meta = [],
        ?string $gatewayEventId = null,
        ?CarbonImmutable $eventAt = null,
    ): Payment {
        $from = $payment->status;

        if ($from === $to) {
            return $payment;
        }

        if (! $from->canTransitionTo($to)) {
            throw ConflictException::make('payment_transition_invalid', context: ['from' => $from->value, 'to' => $to->value]);
        }

        $changes = ['status' => $to] + $attributes;

        if ($to === PaymentStatus::Paid || $to === PaymentStatus::CashCollected) {
            $changes['paid_at'] = $eventAt ?? CarbonImmutable::now();
        }

        if ($eventAt !== null && ($payment->last_event_at === null || $eventAt->greaterThan($payment->last_event_at))) {
            $changes['last_event_at'] = $eventAt;
        }

        $this->payments->update($payment, $changes);
        $this->record($payment, $eventType, $from, $to, $actor, $meta, $gatewayEventId);
        $this->mirrorToBooking($payment, $to);

        return $payment;
    }

    /**
     * A timeline row without a status change (webhook ignored, amount mismatch, settlement posted ...).
     *
     * @param array<string, mixed> $meta
     */
    public function note(Payment $payment, PaymentEventType $type, PaymentActor $actor, array $meta = [], ?string $gatewayEventId = null): void
    {
        $this->record($payment, $type, null, null, $actor, $meta, $gatewayEventId);
    }

    /**
     * Opening row of a payment that starts in its first status (no previous status).
     *
     * @param array<string, mixed> $meta
     */
    public function opened(Payment $payment, PaymentEventType $type, PaymentActor $actor, array $meta = []): void
    {
        $this->record($payment, $type, null, $payment->status, $actor, $meta, null);
        $this->mirrorToBooking($payment, $payment->status);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function record(Payment $payment, PaymentEventType $type, ?PaymentStatus $from, ?PaymentStatus $to, PaymentActor $actor, array $meta, ?string $gatewayEventId): void
    {
        $this->events->append([
            'payment_id' => $payment->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'source' => $actor->source,
            'actor_type' => $actor->type?->value,
            'actor_id' => $actor->id,
            'gateway_event_id' => $gatewayEventId,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    /**
     * Tells the customer about the payment outcomes that matter (SRS §14.1): processing, received, failed, refunded. Cash
     * statuses and a tip are not announced; the text never includes the amount.
     */
    private function notifyCustomer(Payment $payment, Booking $booking, PaymentStatus $status): void
    {
        if ($payment->kind !== PaymentKind::Trip) {
            return;
        }

        $event = match ($status) {
            PaymentStatus::Pending, PaymentStatus::RequiresAction => NotificationEvent::PaymentPending,
            PaymentStatus::Paid => NotificationEvent::PaymentSucceeded,
            PaymentStatus::Failed => NotificationEvent::PaymentFailed,
            PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded => NotificationEvent::PaymentRefunded,
            default => null,
        };

        if ($event !== null) {
            $this->notifier->customer($booking, $event, $payment->uuid.':'.($status === PaymentStatus::RequiresAction ? PaymentStatus::Pending->value : $status->value));
        }
    }

    /**
     * The booking shows the status of its latest trip payment; a tip never changes it (it is still announced).
     */
    private function mirrorToBooking(Payment $payment, PaymentStatus $status): void
    {
        $booking = $this->bookings->find($payment->booking_id);

        if (! $booking instanceof Booking) {
            return;
        }

        if ($payment->kind === PaymentKind::Trip && $booking->payment_status !== $status) {
            $this->bookings->forceUpdate($booking, ['payment_status' => $status]);
        }

        $sequence = $this->sequencer->next($booking->uuid);
        $payload = ['payment_uuid' => $payment->uuid, 'kind' => $payment->kind->value, 'payment_status' => $status->value];

        $booking->loadMissing('customer');
        $this->notifyCustomer($payment, $booking, $status);
        PaymentStatusChanged::toCustomer($booking->customer->uuid, $booking->uuid, $sequence, $payload);

        if ($booking->provider_id !== null && $payment->kind !== PaymentKind::Debt) {
            $provider = $this->providers->find($booking->provider_id);

            if ($provider instanceof ProviderProfile) {
                PaymentStatusChanged::toProvider($provider->uuid, $booking->uuid, $sequence, $payload);
            }
        }
    }
}
