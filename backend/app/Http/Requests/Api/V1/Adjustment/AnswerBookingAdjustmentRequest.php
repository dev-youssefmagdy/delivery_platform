<?php

namespace App\Http\Requests\Api\V1\Adjustment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve / reject / withdraw an adjustment: an optional note shown to the other party.
 */
class AnswerBookingAdjustmentRequest extends FormRequest
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
        return ['note' => ['nullable', 'string', 'max:500']];
    }

    public function note(): ?string
    {
        return $this->filled('note') ? trim((string) $this->input('note')) : null;
    }
}
