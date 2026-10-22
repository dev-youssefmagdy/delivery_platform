<?php

namespace App\Http\Requests\Api\V1\Courier;

use App\Enums\Booking\DeliveryAttemptResolution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST .../delivery-attempts/{attempt}/resolve (customer and provider twins).
 */
class ResolveDeliveryAttemptRequest extends FormRequest
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
        return ['resolution' => ['required', 'string', Rule::enum(DeliveryAttemptResolution::class)]];
    }

    public function resolution(): DeliveryAttemptResolution
    {
        return DeliveryAttemptResolution::from((string) $this->input('resolution'));
    }
}
