<?php

namespace App\Http\Requests\Admin\Pricing;

use App\Models\FarePolicy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Update a fare policy. The scope (service and zone) is fixed after creation: a different scope is a new policy.
 */
class UpdateFarePolicyRequest extends StoreFarePolicyRequest
{
    public function authorize(): bool
    {
        $policy = $this->route('farePolicy');

        return $policy instanceof FarePolicy && Gate::allows('update', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['service_category_id', 'service_zone_id']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
