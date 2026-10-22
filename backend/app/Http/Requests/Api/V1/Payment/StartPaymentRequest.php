<?php

namespace App\Http\Requests\Api\V1\Payment;

use App\DTOs\Payments\StartPaymentData;
use App\Enums\Payments\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /bookings/{booking}/payment-intents and POST /bookings/{booking}/tips. The client names HOW to pay (a saved method
 * or a method type, the wallet phone) and, for a tip, the tip amount. It never sends the trip amount: that is the
 * server's stored price breakdown (Plan I12).
 */
class StartPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['nullable', 'uuid'],
            'method' => ['nullable', 'string', Rule::in([PaymentMethodType::Card->value, PaymentMethodType::MobileWallet->value])],
            'wallet_phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'amount_minor' => [$this->routeIs('*.tips.store') ? 'required' : 'prohibited', 'integer', 'min:1', 'max:9000000000000000'],
        ];
    }

    public function toDto(): StartPaymentData
    {
        return new StartPaymentData(
            paymentMethodId: $this->filled('payment_method_id') ? (string) $this->input('payment_method_id') : null,
            methodType: $this->filled('method') ? PaymentMethodType::from((string) $this->input('method')) : null,
            walletPhone: $this->filled('wallet_phone') ? (string) $this->input('wallet_phone') : null,
            tipAmountMinor: $this->filled('amount_minor') ? $this->integer('amount_minor') : null,
        );
    }
}
