<?php

namespace App\Services\Payments;

use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Payments\PaymentActor;
use App\DTOs\Payments\PaymentIntentResult;
use App\DTOs\Payments\StartPaymentData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Payments\Contracts\PaymentGateway;
use App\Integrations\Payments\Data\PaymentIntentRequest;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Booking\CancellationAbuseService;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Starts online payments (SRS §12.1, §6.7): the trip fare of a card / wallet booking and tips.
 *
 * DECISION (ADR-116): the charge is created AT COMPLETION, when the booking is `completed_pending_payment`; there is no
 * authorization at selection (no hold to release when a booking is cancelled or re-dispatched, and the fare is final only
 * at completion). The amount comes from the booking's stored price breakdown, never from the client (Plan I12). A tip is
 * a separate charge the customer chooses after the trip (never preselected), bounded by the tip settings.
 *
 * Starting a payment never marks anything paid: the intent is `pending` / `requires_action` until the gateway's verified
 * webhook (or a status query) says otherwise (Plan I8). Asking again while an attempt is open returns that attempt, so a
 * retried request or a customer who closed the app resumes instead of paying twice.
 */
final class PaymentIntentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentRepositoryInterface $payments,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PaymentMethodService $methods,
        private readonly PaymentTransitionService $transitions,
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
        private readonly DatabaseManager $db,
        private readonly CancellationAbuseService $abuse,
    ) {}

    public function payTrip(User $customer, string $bookingUuid, StartPaymentData $data): PaymentIntentResult
    {
        $gateway = $this->gateways->default();
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->db->transaction(function () use ($customer, $booking, $data, $gateway): PaymentIntentResult {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if (! in_array($locked->payment_method, [PaymentMethodType::Card, PaymentMethodType::MobileWallet], true)) {
                throw BusinessRuleViolation::make('payment_method_unsupported');
            }

            if ($locked->status !== BookingStatus::CompletedPendingPayment) {
                throw BusinessRuleViolation::make('booking_not_payable');
            }

            $open = $this->openAttempt($locked, PaymentKind::Trip);

            if ($open instanceof PaymentIntentResult) {
                return $open;
            }

            $currency = $this->currencies->get($locked->currency);
            $breakdown = PriceBreakdown::fromArray((array) $locked->price_breakdown, $currency);
            $amount = $breakdown->customerPayable->subtract($breakdown->tip);

            if (! $amount->isPositive()) {
                throw BusinessRuleViolation::make('payment_nothing_to_pay');
            }

            return $this->start($customer, $locked, $gateway, PaymentKind::Trip, $amount, $data, $locked->payment_method, 'Trip '.$locked->code);
        });
    }

    public function tip(User $customer, string $bookingUuid, StartPaymentData $data): PaymentIntentResult
    {
        $gateway = $this->gateways->default();
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->db->transaction(function () use ($customer, $booking, $data, $gateway): PaymentIntentResult {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            $this->assertTipAllowed($locked);
            $currency = $this->currencies->get($locked->currency);
            $minor = $data->tipAmountMinor;
            $max = $this->maxTipMinor($locked);

            if ($minor === null || $minor <= 0) {
                throw BusinessRuleViolation::make('tip_amount_invalid', 'amount');
            }

            if ($minor > $max) {
                throw BusinessRuleViolation::make('tip_amount_too_large', 'amount', ['max' => $max]);
            }

            $open = $this->openAttempt($locked, PaymentKind::Tip);

            if ($open instanceof PaymentIntentResult) {
                return $open;
            }

            if ($this->payments->tipTotalMinor($locked->id) > 0) {
                throw BusinessRuleViolation::make('tip_already_given');
            }

            return $this->start($customer, $locked, $gateway, PaymentKind::Tip, Money::of($minor, $currency), $data, PaymentMethodType::Card, 'Tip '.$locked->code);
        });
    }

    /**
     * Settles the customer's wallet debt (unpaid cancellation fees) through the normal payment flow (ADR-143): the amount is
     * the debt the server sees now, never the client's; paid only by the gateway's verified answer, which credits the wallet.
     */
    public function settleDebt(User $customer, StartPaymentData $data): PaymentIntentResult
    {
        $gateway = $this->gateways->default();

        return $this->db->transaction(function () use ($customer, $data, $gateway): PaymentIntentResult {
            $account = $this->abuse->debtAccount($customer);

            if ($account === null) {
                throw BusinessRuleViolation::make('payment_nothing_to_pay');
            }

            $anchor = $this->bookings->latestFeeBookingOfCustomer($customer->id) ?? throw BusinessRuleViolation::make('payment_nothing_to_pay');
            $locked = $this->bookings->lockById($anchor->id) ?? throw BusinessRuleViolation::make('payment_nothing_to_pay');
            $open = $this->openAttempt($locked, PaymentKind::Debt);

            if ($open instanceof PaymentIntentResult) {
                return $open;
            }

            $amount = Money::of(-$account->balance_minor, $this->currencies->get($account->currency));

            return $this->start($customer, $locked, $gateway, PaymentKind::Debt, $amount, $data, PaymentMethodType::Card, 'Wallet debt');
        });
    }

    /**
     * The saved resume data of an open attempt, encrypted in the payment meta.
     *
     * @return array{client_secret: string|null, action_url: string|null}
     */
    public function actionOf(Payment $payment): array
    {
        $stored = $payment->meta['action'] ?? null;

        if (! is_string($stored)) {
            return ['client_secret' => null, 'action_url' => null];
        }

        try {
            /** @var array<string, string|null> $action */
            $action = json_decode(Crypt::decryptString($stored), true) ?: [];
        } catch (Throwable) {
            return ['client_secret' => null, 'action_url' => null];
        }

        return ['client_secret' => $action['client_secret'] ?? null, 'action_url' => $action['action_url'] ?? null];
    }

    private function assertTipAllowed(Booking $booking): void
    {
        if (! $this->settings->bool(SettingKey::TipsEnabled) || ! (bool) ($booking->policy_snapshot['payments']['tipping_enabled'] ?? false)) {
            throw BusinessRuleViolation::make('tips_not_available');
        }

        if ($booking->status !== BookingStatus::Completed || $booking->provider_id === null) {
            throw BusinessRuleViolation::make('tip_booking_not_completed');
        }
    }

    private function maxTipMinor(Booking $booking): int
    {
        $fare = $booking->final_fare_minor ?? $booking->agreed_fare_minor ?? 0;
        $currency = $this->currencies->get($booking->currency);

        return max(1, Money::of($fare, $currency)->multiplyBasisPoints($this->settings->int(SettingKey::TipMaxBasisPoints))->amount);
    }

    /**
     * An attempt that still awaits the gateway is returned as is; one that needs a person to look at it blocks a new attempt
     * (it may still turn out to be paid, and a second charge would pay twice).
     */
    private function openAttempt(Booking $booking, PaymentKind $kind): ?PaymentIntentResult
    {
        $open = $this->payments->openAttempt($booking->id, $kind);

        if ($open instanceof Payment) {
            $action = $this->actionOf($open);

            return new PaymentIntentResult($open, $action['client_secret'], $action['action_url'], replayed: true);
        }

        $latest = $this->payments->forBooking($booking->id, $kind)->first();

        if ($latest instanceof Payment && $latest->status === PaymentStatus::ReconciliationRequired) {
            throw BusinessRuleViolation::make('payment_needs_review');
        }

        return null;
    }

    private function start(User $customer, Booking $booking, PaymentGateway $gateway, PaymentKind $kind, Money $amount, StartPaymentData $data, PaymentMethodType $default, string $description): PaymentIntentResult
    {
        $saved = $this->methods->resolveOwned($customer, $data->paymentMethodId);
        $type = $saved->type ?? $data->methodType ?? $default;

        if (! in_array($type, [PaymentMethodType::Card, PaymentMethodType::MobileWallet], true)) {
            throw BusinessRuleViolation::make('payment_method_unsupported', 'payment_method');
        }

        $phone = $data->walletPhone ?? $customer->phone_e164;

        if ($type === PaymentMethodType::MobileWallet && ($phone === null || $phone === '')) {
            throw BusinessRuleViolation::make('wallet_phone_required', 'wallet_phone');
        }

        $timeout = $this->settings->int(SettingKey::PaymentIntentTimeoutMinutes);
        $payment = $this->payments->insert([
            'uuid' => (string) Str::uuid(),
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'provider_id' => $kind === PaymentKind::Debt ? null : $booking->provider_id,
            'kind' => $kind,
            'method_type' => $type,
            'payment_method_id' => $saved?->id,
            'gateway' => $gateway->name(),
            'amount_minor' => $amount->amount,
            'currency' => $amount->currency->code,
            'status' => PaymentStatus::Pending,
            'attempt' => $this->payments->attemptCount($booking->id, $kind) + 1,
            'expires_at' => CarbonImmutable::now()->addMinutes($timeout),
        ]);
        $this->transitions->opened($payment, PaymentEventType::IntentCreated, PaymentActor::customer($customer), ['kind' => $kind->value, 'method' => $type->value, 'attempt' => $payment->attempt]);

        try {
            $intent = $gateway->createIntent(new PaymentIntentRequest(
                reference: $payment->uuid,
                amount: $amount,
                method: $type,
                description: $description,
                savedMethodToken: $saved instanceof PaymentMethod ? $saved->gateway_token : null,
                customerName: $customer->name,
                customerPhone: $phone,
                customerEmail: $customer->email,
                returnUrl: config('payments.return_url') ?: null,
            ));
        } catch (PaymentGatewayException $exception) {
            \App\Services\System\IntegrationStatusStore::failed(\App\Enums\System\HealthArea::Payments, $exception);
            Log::error('payments.intent.gateway_failed', ['payment' => $payment->uuid, 'gateway' => $gateway->name()->value, 'exception' => $exception::class]);

            throw ServiceUnavailableException::make('payment_gateway_unavailable');
        }

        $this->payments->update($payment, [
            'gateway_reference' => $intent->reference,
            'expires_at' => $intent->expiresAt ?? $payment->expires_at,
            'meta' => array_filter([
                'action' => Crypt::encryptString((string) json_encode(['client_secret' => $intent->clientSecret, 'action_url' => $intent->actionUrl])),
            ] + $intent->meta, static fn (mixed $value): bool => $value !== null),
        ]);

        if ($intent->status === PaymentStatus::RequiresAction) {
            $this->transitions->transition($payment, PaymentStatus::RequiresAction, PaymentEventType::StatusChanged, PaymentActor::system());
        }

        return new PaymentIntentResult($payment, $intent->clientSecret, $intent->actionUrl);
    }
}
