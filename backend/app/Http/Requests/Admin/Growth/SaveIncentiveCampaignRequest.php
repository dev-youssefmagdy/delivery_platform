<?php

namespace App\Http\Requests\Admin\Growth;

use App\Models\IncentiveCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Create / update of a provider incentive campaign.
 */
class SaveIncentiveCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('incentiveCampaign');

        return $campaign instanceof IncentiveCampaign ? Gate::allows('update', $campaign) : Gate::allows('create', IncentiveCampaign::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i', 'after:starts_at'],
            'target_jobs' => ['required', 'integer', 'min:1', 'max:1000'],
            'bonus_minor' => ['required', 'integer', 'min:1', 'max:100000000'],
            'max_awards_per_provider' => ['required', 'integer', 'min:1', 'max:100'],
            'budget_minor' => ['required', 'integer', 'min:1', 'max:1000000000000', 'gte:bonus_minor'],
            'min_order_minor' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'service_category_ids' => ['nullable', 'array', 'max:100'],
            'service_category_ids.*' => ['integer', 'exists:service_categories,id'],
            'city_ids' => ['nullable', 'array', 'max:100'],
            'city_ids.*' => ['integer', 'exists:cities,id'],
            'max_jobs_per_customer' => ['required', 'integer', 'min:1', 'max:100'],
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
            'starts_at' => (string) __('growth.fields.starts_at'),
            'ends_at' => (string) __('growth.fields.ends_at'),
            'target_jobs' => (string) __('growth.incentive.fields.target'),
            'bonus_minor' => (string) __('growth.incentive.fields.bonus'),
            'max_awards_per_provider' => (string) __('growth.incentive.fields.max_awards'),
            'budget_minor' => (string) __('growth.fields.budget'),
            'min_order_minor' => (string) __('growth.fields.min_order'),
            'min_rating' => (string) __('growth.incentive.fields.min_rating'),
            'max_jobs_per_customer' => (string) __('growth.incentive.fields.max_per_customer'),
        ];
    }
}
