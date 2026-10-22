<?php

namespace App\Http\Requests\Api\V1\Booking;

use App\DTOs\Booking\UpdateBookingRequestData;
use App\Http\Requests\Api\V1\Booking\Concerns\BuildsLocations;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * PATCH /bookings/{booking}/request: any subset of the changeable parts of a searching booking. The category, booking
 * type and payment method are not changeable here (cancel and book again).
 */
class UpdateBookingRequestRequest extends FormRequest
{
    use BuildsLocations;

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
            'scheduled_at' => ['nullable', 'date'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:100'],
            'service_details' => ['sometimes', 'array', 'max:60'],
            'proposed_fare_minor' => ['nullable', 'integer', 'min:1', 'max:9000000000000000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'stops' => ['sometimes', 'array', 'max:10'],
            'stops.*' => ['array'],
            'stops.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'stops.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'stops.*.address' => ['nullable', 'array'],
            'stops.*.address.title' => ['nullable', 'string', 'max:191'],
            'stops.*.address.line' => ['nullable', 'string', 'max:'.(int) config('booking.address_line_max', 255)],
            'stops.*.contact' => ['nullable', 'array'],
            'stops.*.contact.name' => ['required_with:stops.*.contact', 'string', 'max:100'],
            'stops.*.contact.phone' => ['required_with:stops.*.contact', 'string', 'max:32'],
            'stops.*.contact.note' => ['nullable', 'string', 'max:255'],
        ]
            + $this->locationRules('pickup', false)
            + $this->locationRules('dropoff', false);
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->rejectImpossibleLocation($validator, 'pickup');
            $this->rejectImpossibleLocation($validator, 'dropoff');

            if (! $this->toDto()->changesAnything() && ! $validator->errors()->isNotEmpty()) {
                $validator->errors()->add('request', __('api.errors.booking_request_empty'));
            }
        }];
    }

    public function toDto(): UpdateBookingRequestData
    {
        return new UpdateBookingRequestData(
            pickup: $this->location('pickup'),
            dropoff: $this->location('dropoff'),
            hasStops: $this->has('stops'),
            stops: $this->stopLocations(),
            scheduledAt: $this->filled('scheduled_at') ? CarbonImmutable::parse((string) $this->input('scheduled_at')) : null,
            passengers: $this->filled('passengers') ? (int) $this->input('passengers') : null,
            hasServiceDetails: $this->has('service_details'),
            serviceDetails: (array) $this->input('service_details', []),
            proposedFareMinor: $this->filled('proposed_fare_minor') ? (int) $this->input('proposed_fare_minor') : null,
            currency: $this->filled('currency') ? (string) $this->input('currency') : null,
        );
    }
}
