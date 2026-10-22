<?php

namespace App\Services\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentMethodType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Payments\Data\SetupIntentRequest;
use App\Integrations\Payments\Data\VerifiedWebhook;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Integrations\Payments\Exceptions\UnsupportedPaymentOperationException;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Repositories\Contracts\Payments\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Saved online payment methods (SRS §12.1, §20.5). The platform keeps a gateway token reference (encrypted) with brand
 * and last four digits; the card itself is entered only in the processor's UI. A setup intent creates a `pending` row
 * that the processor's verified webhook activates, so a saved method exists only if the processor confirmed it.
 */
final class PaymentMethodService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentMethodRepositoryInterface $methods,
        private readonly PaymentRepositoryInterface $payments,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function list(User $customer): Collection
    {
        return $this->methods->activeForUser($customer->id);
    }

    /**
     * Starts saving a card or wallet. Returns the pending method and what the app must open.
     *
     * @return array{method: PaymentMethod, client_secret: string|null, action_url: string|null}
     */
    public function setup(User $customer, PaymentMethodType $type): array
    {
        if ($type !== PaymentMethodType::Card && $type !== PaymentMethodType::MobileWallet) {
            throw BusinessRuleViolation::make('payment_method_type_unsupported', 'type');
        }

        $gateway = $this->gateways->default();
        $reference = (string) Str::uuid();

        try {
            $intent = $gateway->createSetupIntent(new SetupIntentRequest(
                reference: $reference,
                method: $type,
                customerName: $customer->name,
                customerPhone: $customer->phone_e164,
                customerEmail: $customer->email,
                returnUrl: config('payments.return_url') ?: null,
            ));
        } catch (UnsupportedPaymentOperationException) {
            throw BusinessRuleViolation::make('payment_setup_not_supported');
        } catch (PaymentGatewayException) {
            throw ServiceUnavailableException::make('payment_gateway_unavailable');
        }

        $method = $this->methods->insert([
            'user_id' => $customer->id,
            'type' => $type,
            'gateway' => $gateway->name(),
            'status' => PaymentMethod::STATUS_PENDING,
            'setup_reference' => $intent->reference,
        ]);

        return ['method' => $method, 'client_secret' => $intent->clientSecret, 'action_url' => $intent->actionUrl];
    }

    /**
     * The processor reported a saved method (verified webhook). Matches the pending row of a setup intent, or the customer
     * of the payment during which the card was saved. Returns false when nobody owns the event yet.
     */
    public function activateFromWebhook(PaymentGatewayName $gateway, VerifiedWebhook $event): bool
    {
        if ($event->methodToken === null) {
            return false;
        }

        return $this->db->transaction(function () use ($gateway, $event): bool {
            $method = $event->setupReference !== null ? $this->methods->findBySetupReference($gateway, $event->setupReference) : null;

            if ($method instanceof PaymentMethod && $method->isActive()) {
                return true;
            }

            if (! $method instanceof PaymentMethod) {
                $payment = $event->gatewayReference !== null ? $this->payments->findByGatewayReference($gateway, $event->gatewayReference) : null;
                $payment ??= $event->gatewayReference !== null ? $this->paymentOfGatewayOrder($event->gatewayReference) : null;

                if (! $payment instanceof Payment) {
                    return false;
                }

                $method = $this->methods->insert([
                    'user_id' => $payment->user_id,
                    'type' => PaymentMethodType::Card,
                    'gateway' => $gateway,
                    'status' => PaymentMethod::STATUS_PENDING,
                    'setup_reference' => $event->setupReference,
                ]);
            }

            $first = $this->methods->activeCount($method->user_id) === 0;
            $this->methods->update($method, [
                'status' => PaymentMethod::STATUS_ACTIVE,
                'gateway_token' => $event->methodToken,
                'brand' => $event->methodBrand !== null ? mb_substr($event->methodBrand, 0, 24) : null,
                'last4' => $event->methodLast4,
                'is_default' => $first,
            ]);

            return true;
        });
    }

    /**
     * @throws ModelNotFoundException another customer's method is a 404
     */
    public function remove(User $customer, string $uuid): void
    {
        $method = $this->methods->findOwned($uuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(PaymentMethod::class);

        $this->db->transaction(function () use ($method, $customer): void {
            if ($method->gateway_token !== null && ($gateway = $this->gateways->forName($method->gateway)) !== null) {
                try {
                    $gateway->detachMethod($method->gateway_token);
                } catch (PaymentGatewayException) {
                    // Best effort at the processor: the platform forgets the token either way.
                }
            }

            $wasDefault = $method->is_default;
            $this->methods->update($method, ['gateway_token' => null, 'is_default' => false]);
            $this->methods->delete($method);

            if ($wasDefault) {
                $next = $this->methods->activeForUser($customer->id)->first();

                if ($next instanceof PaymentMethod) {
                    $this->methods->update($next, ['is_default' => true]);
                }
            }
        });
    }

    /**
     * The saved method a customer picked for a payment (own and active), or null when none was named.
     */
    public function resolveOwned(User $customer, ?string $uuid): ?PaymentMethod
    {
        if ($uuid === null) {
            return null;
        }

        return $this->methods->findOwned($uuid, $customer->id) ?? throw BusinessRuleViolation::make('payment_method_not_found', 'payment_method_id');
    }

    /**
     * Paymob names the order, not our reference, in the card-token callback: such events carry `order:<id>`.
     */
    private function paymentOfGatewayOrder(string $reference): ?Payment
    {
        if (! str_starts_with($reference, 'order:')) {
            return null;
        }

        return $this->payments->findByGatewayOrderId(substr($reference, 6));
    }
}
