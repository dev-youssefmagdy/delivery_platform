<?php

namespace App\Http\Requests\Admin\Growth;

use App\Enums\Growth\ReferralAudience;
use App\Models\ReferralCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create / update of a referral campaign. On update the route carries the campaign (its audience is then fixed).
 */
class SaveReferralCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('referralCampaign');

        return $campaign instanceof ReferralCampaign ? Gate::allows('update', $campaign) : Gate::allows('create', ReferralCampaign::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $editing = $this->route('referralCampaign') instanceof ReferralCampaign;

        return [
            'name' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'audience' => [$editing ? 'nullable' : 'required', Rule::enum(ReferralAudience::class)],
            'starts_at' => ['nullable', 'date_format:Y-m-d H:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d H:i', 'after:starts_at'],
            'referrer_reward_minor' => ['required', 'integer', 'min:0', 'max:100000000'],
            'referee_reward_minor' => ['required', 'integer', 'min:0', 'max:100000000'],
            'milestone_jobs' => ['required', 'integer', 'min:1', 'max:50'],
            'min_order_minor' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'service_category_ids' => ['nullable', 'array', 'max:100'],
            'service_category_ids.*' => ['integer', 'exists:service_categories,id'],
            'city_ids' => ['nullable', 'array', 'max:100'],
            'city_ids.*' => ['integer', 'exists:cities,id'],
            'credit_valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'per_referrer_cap' => ['required', 'integer', 'min:1', 'max:1000'],
            'budget_minor' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('growth.fields.name'),
            'name_en' => (string) __('growth.fields.name_en'),
            'audience' => (string) __('growth.fields.audience'),
            'starts_at' => (string) __('growth.fields.starts_at'),
            'ends_at' => (string) __('growth.fields.ends_at'),
            'referrer_reward_minor' => (string) __('growth.referral.fields.referrer_reward'),
            'referee_reward_minor' => (string) __('growth.referral.fields.referee_reward'),
            'milestone_jobs' => (string) __('growth.referral.fields.milestone'),
            'min_order_minor' => (string) __('growth.fields.min_order'),
            'credit_valid_days' => (string) __('growth.referral.fields.valid_days'),
            'per_referrer_cap' => (string) __('growth.referral.fields.cap'),
            'budget_minor' => (string) __('growth.fields.budget'),
        ];
    }
}
