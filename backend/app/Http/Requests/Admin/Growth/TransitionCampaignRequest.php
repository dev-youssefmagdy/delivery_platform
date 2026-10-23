<?php

namespace App\Http\Requests\Admin\Growth;

use App\Enums\Growth\CampaignStatus;
use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\IncentiveCampaign;
use App\Models\ReferralCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moves a referral or incentive campaign to another status (activate, pause, archive). Pausing and archiving need a reason.
 */
class TransitionCampaignRequest extends FormRequest
{
    use HasReason {
        attributes as reasonAttributes;
    }

    public function authorize(): bool
    {
        $campaign = $this->route('referralCampaign') ?? $this->route('incentiveCampaign');

        return ($campaign instanceof ReferralCampaign || $campaign instanceof IncentiveCampaign) && Gate::allows('transition', [$campaign, $this->target()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(CampaignStatus::class)]] + $this->reasonRules($this->target()?->needsReason() ?? false);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->reasonAttributes() + ['status' => (string) __('growth.fields.status')];
    }

    public function target(): ?CampaignStatus
    {
        return CampaignStatus::tryFrom((string) $this->input('status'));
    }
}
