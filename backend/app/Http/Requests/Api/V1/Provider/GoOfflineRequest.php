<?php

namespace App\Http\Requests\Api\V1\Provider;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /provider/availability/offline. `confirm` acknowledges that a job in progress continues.
 */
class GoOfflineRequest extends FormRequest
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
        return ['confirm' => ['sometimes', 'boolean']];
    }

    public function confirmed(): bool
    {
        return $this->boolean('confirm');
    }
}
