<?php

namespace App\Http\Requests\Api\V1\Payment;

use App\Enums\Payments\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /me/payment-methods/setup-intent: which kind of method the customer wants to save.
 */
class SetupIntentRequest extends FormRequest
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
        return ['type' => ['required', 'string', Rule::in([PaymentMethodType::Card->value, PaymentMethodType::MobileWallet->value])]];
    }

    public function methodType(): PaymentMethodType
    {
        return PaymentMethodType::from((string) $this->input('type'));
    }
}
