<?php

namespace App\Http\Requests\Api\V1\Reviews;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /ratings/{rating}/appeal: the person explains why a hidden review or a low rating should be looked at again.
 */
class AppealRatingRequest extends FormRequest
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
        return ['message' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
