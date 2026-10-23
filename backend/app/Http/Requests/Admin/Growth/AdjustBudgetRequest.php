<?php

namespace App\Http\Requests\Admin\Growth;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\IncentiveCampaign;
use App\Models\ReferralCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Budget adjustment of a campaign: a new total budget with a mandatory reason (audited with before / after).
 */
class AdjustBudgetRequest extends FormRequest
{
    use HasReason {
        attributes as reasonAttributes;
    }

    public function authorize(): bool
    {
        $campaign = $this->route('referralCampaign') ?? $this->route('incentiveCampaign');

        return ($campaign instanceof ReferralCampaign || $campaign instanceof IncentiveCampaign) && Gate::allows('adjust', $campaign);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $campaign = $this->route('referralCampaign') ?? $this->route('incentiveCampaign');
        $spent = $campaign instanceof ReferralCampaign || $campaign instanceof IncentiveCampaign ? $campaign->spent_minor : 0;

        return ['budget_minor' => ['required', 'integer', 'min:'.max(1, $spent), 'max:1000000000000']] + $this->reasonRules(true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->reasonAttributes() + ['budget_minor' => (string) __('growth.fields.budget')];
    }
}
