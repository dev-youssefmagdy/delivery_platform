<?php

namespace App\Http\Requests\Api\V1\Offer;

use App\DTOs\Booking\SubmitOfferData;
use App\Http\Middleware\Idempotency;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /provider/bookings/{booking}/offers/accept-customer-fare: no amount is accepted (it is the customer's fare,
 * read from the booking); only the arrival estimate the provider states.
 */
class AcceptCustomerFareRequest extends FormRequest
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
            'eta_minutes' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('booking.offers.eta_max_minutes', 120)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toDto(): SubmitOfferData
    {
        return new SubmitOfferData(
            note: $this->filled('note') ? (string) $this->input('note') : null,
            etaMinutes: $this->filled('eta_minutes') ? $this->integer('eta_minutes') : null,
            idempotencyKey: $this->header(Idempotency::HEADER),
        );
    }
}
