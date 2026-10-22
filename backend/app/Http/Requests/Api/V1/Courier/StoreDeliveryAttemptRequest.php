<?php

namespace App\Http\Requests\Api\V1\Courier;

use App\DTOs\Courier\DeliveryAttemptData;
use App\Enums\Booking\DeliveryAttemptOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /provider/bookings/{booking}/delivery-attempts (multipart when evidence is attached).
 */
class StoreDeliveryAttemptRequest extends FormRequest
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
            'outcome' => ['required', 'string', Rule::enum(DeliveryAttemptOutcome::class)],
            'reason' => ['nullable', 'string', 'max:'.(int) config('courier.attempts.reason_max_chars', 500)],
            'evidence' => ['nullable', 'file', 'max:'.(int) config('courier.proofs.max_kilobytes', 5120)],
        ];
    }

    public function toDto(): DeliveryAttemptData
    {
        return new DeliveryAttemptData(
            DeliveryAttemptOutcome::from((string) $this->input('outcome')),
            $this->filled('reason') ? (string) $this->input('reason') : null,
            $this->file('evidence'),
        );
    }
}
