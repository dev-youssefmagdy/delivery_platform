<?php

namespace App\Http\Requests\Api\V1\Payout;

use App\DTOs\Payout\PayoutRequestData;
use App\Http\Middleware\Idempotency;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /provider/payouts: the amount (minor units) and the verified payout method. The fee, the net amount and the approval path
 * are the server's (settings); the Idempotency-Key makes a retry return the first request.
 */
class StorePayoutRequest extends FormRequest
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
            'amount_minor' => ['required', 'integer', 'min:1', 'max:9000000000000000'],
            'payout_method_id' => ['required', 'uuid'],
        ];
    }

    public function toDto(): PayoutRequestData
    {
        return new PayoutRequestData(
            amountMinor: (int) $this->validated('amount_minor'),
            methodUuid: (string) $this->validated('payout_method_id'),
            idempotencyKey: (string) $this->headers->get(Idempotency::HEADER),
        );
    }
}
