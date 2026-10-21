<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\CommissionRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Update a commission rule. The scope (service, zone, tier, campaign) and the currency are fixed after creation: a
 * different scope is a new rule. Changing the numbers adds a version; bookings keep the version they were bound to.
 */
class UpdateCommissionRuleRequest extends StoreCommissionRuleRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('commissionRule');

        return $rule instanceof CommissionRule && Gate::allows('update', $rule);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['service_category_id', 'service_zone_id', 'provider_tier', 'campaign_code']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
