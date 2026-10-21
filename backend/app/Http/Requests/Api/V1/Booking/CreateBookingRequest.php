<?php

namespace App\Http\Requests\Api\V1\Booking;

use App\DTOs\Booking\CreateBookingData;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Http\Middleware\Idempotency;
use App\Http\Requests\Api\V1\Booking\Concerns\BuildsLocations;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LogicException;

/**
 * POST /bookings. Carries the customer's choices only. A total, a status, a snapshot or a provider in the body is
 * ignored: the server computes and decides (Plan I12). Business rules (zone, capacity, fare bounds ...) run in
 * `BookingRequestValidator`, not here.
 */
class CreateBookingRequest extends FormRequest
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
            'service_category_id' => ['required', 'integer', 'min:1'],
            'booking_type' => ['required', 'string', Rule::enum(BookingType::class)],
            'scheduled_at' => ['nullable', 'date'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:100'],
            'service_details' => ['nullable', 'array', 'max:60'],
            'proposed_fare_minor' => ['required', 'integer', 'min:1', 'max:9000000000000000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethodType::class)],
            'stops' => ['nullable', 'array', 'max:10'],
        ]
            + $this->locationRules('pickup', true)
            + $this->locationRules('dropoff', false)
            + $this->stopRules();
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->rejectImpossibleLocation($validator, 'pickup');
            $this->rejectImpossibleLocation($validator, 'dropoff');

            foreach (array_keys((array) $this->input('stops', [])) as $index) {
                $this->rejectImpossibleLocation($validator, "stops.{$index}");
            }
        }];
    }

    public function toDto(): CreateBookingData
    {
        return new CreateBookingData(
            serviceCategoryId: (int) $this->input('service_category_id'),
            bookingType: BookingType::from((string) $this->input('booking_type')),
            pickup: $this->location('pickup') ?? throw new LogicException('pickup was validated'),
            dropoff: $this->location('dropoff'),
            stops: $this->stopLocations(),
            scheduledAt: $this->filled('scheduled_at') ? CarbonImmutable::parse((string) $this->input('scheduled_at')) : null,
            passengers: $this->filled('passengers') ? (int) $this->input('passengers') : null,
            serviceDetails: (array) $this->input('service_details', []),
            proposedFareMinor: (int) $this->input('proposed_fare_minor'),
            currency: $this->filled('currency') ? (string) $this->input('currency') : null,
            paymentMethod: $this->filled('payment_method') ? PaymentMethodType::from((string) $this->input('payment_method')) : PaymentMethodType::Cash,
            idempotencyKey: (string) $this->headers->get(Idempotency::HEADER) ?: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function stopRules(): array
    {
        return [
            'stops.*' => ['array'],
            'stops.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'stops.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'stops.*.address' => ['nullable', 'array'],
            'stops.*.address.title' => ['nullable', 'string', 'max:191'],
            'stops.*.address.line' => ['nullable', 'string', 'max:'.(int) config('booking.address_line_max', 255)],
        ];
    }
}
