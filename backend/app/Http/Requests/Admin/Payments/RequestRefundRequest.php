<?php

namespace App\Http\Requests\Admin\Payments;

use App\DTOs\Payments\RefundRequestData;
use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Ask for a refund of a collected online payment: an amount (minor units, at most what is still refundable), a mandatory
 * reason and the idempotency key generated with the form (a double submit creates one request).
 */
class RequestRefundRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment && Gate::allows('requestRefund', $payment);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $payment = $this->route('payment');
        $max = $payment instanceof Payment ? $payment->refundableMinor() : 0;

        return [
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.max(1, $max)],
            'idempotency_key' => ['required', 'uuid'],
        ] + $this->reasonRules(true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['amount_minor' => (string) __('admin.payments.refund.amount'), 'reason' => (string) __('admin.common.reason')];
    }

    public function toDto(): RefundRequestData
    {
        return new RefundRequestData(
            amountMinor: (int) $this->validated('amount_minor'),
            reason: (string) $this->reason(),
            idempotencyKey: (string) $this->validated('idempotency_key'),
        );
    }
}
