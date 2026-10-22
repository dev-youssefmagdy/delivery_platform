<?php

namespace App\Http\Requests\Api\V1\Adjustment;

use App\DTOs\Booking\BookingAdjustmentData;
use App\Enums\Booking\BookingAdjustmentType;
use App\Http\Middleware\Idempotency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /bookings/{booking}/adjustments and POST /provider/bookings/{booking}/adjustments. The client states what it
 * wants changed and why; the new total is always computed by the server. Toll / parking needs `evidence.reference`
 * (receipt number); a waiting adjustment may omit the amount when the policy prices waiting per minute.
 */
class CreateBookingAdjustmentRequest extends FormRequest
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
            'type' => ['required', Rule::enum(BookingAdjustmentType::class)],
            'amount_minor' => ['nullable', 'integer', 'between:-1000000000,1000000000'],
            'reason' => ['required', 'string', 'min:3', 'max:'.(int) config('booking.adjustments.reason_max_chars', 500)],
            'details' => ['nullable', 'array', 'max:6'],
            'details.address' => ['nullable', 'string', 'max:255'],
            'details.minutes' => ['nullable', 'integer', 'between:0,1440'],
            'evidence' => ['nullable', 'array', 'max:4'],
            'evidence.reference' => [Rule::requiredIf(fn (): bool => $this->input('type') === BookingAdjustmentType::TollParking->value), 'nullable', 'string', 'max:120'],
            'evidence.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): BookingAdjustmentData
    {
        return new BookingAdjustmentData(
            type: BookingAdjustmentType::from((string) $this->input('type')),
            amountMinor: $this->filled('amount_minor') ? $this->integer('amount_minor') : null,
            reason: trim((string) $this->input('reason')),
            details: array_filter((array) $this->input('details', []), static fn (mixed $value): bool => $value !== null && $value !== ''),
            evidence: array_filter((array) $this->input('evidence', []), static fn (mixed $value): bool => $value !== null && $value !== ''),
            idempotencyKey: $this->header(Idempotency::HEADER),
        );
    }
}
