<?php

namespace App\Http\Requests\Admin\Booking;

use App\Models\CancellationPolicy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Update a cancellation policy. The scope (service and zone) is fixed after creation: a different scope is a new policy.
 */
class UpdateCancellationPolicyRequest extends StoreCancellationPolicyRequest
{
    public function authorize(): bool
    {
        $policy = $this->route('cancellationPolicy');

        return $policy instanceof CancellationPolicy && Gate::allows('update', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['service_category_id', 'service_zone_id']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
