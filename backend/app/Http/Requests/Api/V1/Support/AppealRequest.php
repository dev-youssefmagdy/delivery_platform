<?php

namespace App\Http\Requests\Api\V1\Support;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /bookings/{booking}/cancellation-appeal and /provider/bookings/{booking}/cancellation-appeal.
 */
class AppealRequest extends FormRequest
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
        return ['reason' => ['required', 'string', 'min:10', 'max:'.(int) config('support.tickets.body_max_chars', 4000)]];
    }
}
