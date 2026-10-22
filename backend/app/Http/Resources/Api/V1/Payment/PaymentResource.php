<?php

namespace App\Http\Resources\Api\V1\Payment;

use App\DTOs\Payments\PaymentIntentResult;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payment as its customer sees it: state, amount, method and (only right after starting or resuming one) the action the
 * app must open. The gateway reference, meta and ledger links never leave the server. The app treats `status` as a hint and
 * refetches; only the server's verified webhook or query can make it `paid`.
 *
 * @property Payment|PaymentIntentResult $resource
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $intent = $this->resource instanceof PaymentIntentResult ? $this->resource : null;
        $payment = $intent?->payment ?? $this->resource;
        $payment->loadMissing(['booking:id,uuid', 'paymentMethod']);

        return [
            'id' => $payment->uuid,
            'booking_id' => $payment->booking->uuid,
            'kind' => $payment->kind->value,
            'method' => $payment->method_type->value,
            'gateway' => $payment->gateway?->value,
            'status' => ['code' => $payment->status->value, 'label' => $payment->status->label()],
            'amount' => ['minor' => $payment->amount_minor, 'currency' => $payment->currency],
            'refunded_minor' => $payment->refunded_minor,
            'attempt' => $payment->attempt,
            'failure_code' => $payment->failure_code,
            'saved_method' => $payment->paymentMethod === null ? null : ['id' => $payment->paymentMethod->uuid, 'brand' => $payment->paymentMethod->brand, 'last4' => $payment->paymentMethod->last4],
            'expires_at' => $payment->expires_at?->toIso8601ZuluString(),
            'paid_at' => $payment->paid_at?->toIso8601ZuluString(),
            'created_at' => $payment->created_at->toIso8601ZuluString(),
            'action' => $intent === null || ($intent->clientSecret === null && $intent->actionUrl === null) ? null : [
                'client_secret' => $intent->clientSecret,
                'url' => $intent->actionUrl,
            ],
        ];
    }
}
