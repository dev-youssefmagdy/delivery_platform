<?php

namespace App\Http\Requests\Api\V1\Trip;

use App\DTOs\Booking\ArrivalData;
use App\DTOs\Booking\CancelBookingData;
use App\Enums\Booking\CancellationReason;
use App\Enums\Common\ActorType;
use App\Http\Middleware\Idempotency;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /bookings/{booking}/cancel (customer) and POST /provider/bookings/{booking}/cancel (provider). The reason code
 * comes from the actor's own list; `preview=true` only computes the decision; a customer cancellation that owes a fee
 * carries `accepted_fee_minor`, the amount the preview showed. A no-show report (`customer_no_show`, `driver_no_show`) may
 * carry the reporter's GPS fix (`lat`, `lng`, `accuracy_m`, `recorded_at`). The policy's narrower reason list is checked by the service.
 */
class CancelBookingRequest extends FormRequest
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
            'reason_code' => ['required', 'string', Rule::in(CancellationReason::valuesForActor($this->actor()))],
            'note' => ['nullable', 'string', 'max:'.(int) config('booking.trip.cancellation_note_max_chars', 255)],
            'preview' => ['nullable', 'boolean'],
            'accepted_fee_minor' => ['nullable', 'integer', 'min:0'],
            // GPS fix of a no-show report (customer_no_show / driver_no_show): evidence the policy may require.
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }

    public function actor(): ActorType
    {
        return $this->routeIs('api.v1.provider.*') ? ActorType::Provider : ActorType::Customer;
    }

    public function toDto(): CancelBookingData
    {
        return new CancelBookingData(
            reason: CancellationReason::from((string) $this->input('reason_code')),
            note: $this->filled('note') ? (string) $this->input('note') : null,
            preview: $this->boolean('preview'),
            acknowledgedFeeMinor: $this->filled('accepted_fee_minor') ? $this->integer('accepted_fee_minor') : null,
            idempotencyKey: $this->header(Idempotency::HEADER),
            fix: $this->filled('lat') ? new ArrivalData(
                lat: (float) $this->input('lat'),
                lng: (float) $this->input('lng'),
                accuracyMeters: $this->filled('accuracy_m') ? (float) $this->input('accuracy_m') : null,
                recordedAt: $this->filled('recorded_at') ? CarbonImmutable::parse((string) $this->input('recorded_at')) : null,
            ) : null,
        );
    }
}
