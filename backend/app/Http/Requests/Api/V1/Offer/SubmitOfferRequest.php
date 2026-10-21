<?php

namespace App\Http\Requests\Api\V1\Offer;

use App\DTOs\Booking\SubmitOfferData;
use App\Http\Middleware\Idempotency;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * POST /provider/bookings/{booking}/offers: a counter-offer (or the next version of the provider's own offer).
 * Money is integer minor units; the server checks currency and bounds against the booking's snapshot, never the client.
 */
class SubmitOfferRequest extends FormRequest
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
            'currency' => ['nullable', 'string', 'size:3'],
            'note' => ['nullable', 'string', 'max:500'],
            'scope' => ['nullable', 'array', 'max:40'],
            'eta_minutes' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('booking.offers.eta_max_minutes', 120)],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $scope = $this->input('scope');

            if (is_array($scope) && strlen((string) json_encode($scope)) > (int) config('booking.offers.scope_max_bytes', 4096)) {
                $validator->errors()->add('scope', __('validation.max.array', ['attribute' => 'scope', 'max' => 40]));
            }
        }];
    }

    public function toDto(): SubmitOfferData
    {
        $scope = $this->input('scope');

        return new SubmitOfferData(
            amountMinor: $this->integer('amount_minor'),
            currency: $this->filled('currency') ? strtoupper((string) $this->input('currency')) : null,
            note: $this->filled('note') ? (string) $this->input('note') : null,
            scope: is_array($scope) && $scope !== [] ? $scope : null,
            etaMinutes: $this->filled('eta_minutes') ? $this->integer('eta_minutes') : null,
            idempotencyKey: $this->header(Idempotency::HEADER),
        );
    }
}
