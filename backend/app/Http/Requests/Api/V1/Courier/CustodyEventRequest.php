<?php

namespace App\Http\Requests\Api\V1\Courier;

use App\Enums\Booking\CustodyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /provider/bookings/{booking}/custody. Only the steps the provider reports by hand; picked_up, delivered, attempt_failed
 * and returned are written by the start, complete and attempt actions.
 */
class CustodyEventRequest extends FormRequest
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
        return ['event' => ['required', 'string', Rule::in([CustodyStatus::InTransit->value, CustodyStatus::ArrivedDropoff->value])]];
    }

    public function event(): CustodyStatus
    {
        return CustodyStatus::from((string) $this->input('event'));
    }
}
