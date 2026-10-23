<?php

namespace App\Http\Requests\Api\V1\Growth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /me/referral/apply: the referral code a new customer or provider received.
 */
class ApplyReferralRequest extends FormRequest
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
        return ['code' => ['required', 'string', 'min:4', 'max:20']];
    }
}
