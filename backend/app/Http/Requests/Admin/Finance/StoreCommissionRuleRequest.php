<?php

namespace App\Http\Requests\Admin\Finance;

use App\DTOs\Finance\CommissionRuleData;
use App\Enums\Finance\CommissionType;
use App\Enums\Settings\SettingKey;
use App\Models\CommissionRule;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a commission rule. Amounts arrive in minor units (the money input submits them that way), the percentage in
 * percent. Overlap, range and scope checks are repeated by `CommissionRuleService` under lock.
 */
class StoreCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', CommissionRule::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $money = ['nullable', 'integer', 'min:0', 'max:10000000000'];

        return [
            'name' => ['required', 'string', 'max:120'],
            'service_category_id' => ['nullable', 'integer', Rule::exists('service_categories', 'id')],
            'service_zone_id' => ['nullable', 'integer', Rule::exists('service_zones', 'id')],
            'provider_tier' => ['nullable', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
            'campaign_code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
            'priority' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'effective_from' => ['required', 'date_format:Y-m-d H:i'],
            'effective_to' => ['nullable', 'date_format:Y-m-d H:i', 'after:effective_from'],
            'type' => ['required', 'string', Rule::enum(CommissionType::class)],
            'percent' => ['nullable', 'required_unless:type,'.CommissionType::Fixed->value, 'numeric', 'min:0', 'max:100'],
            'fixed_minor' => ['nullable', 'required_unless:type,'.CommissionType::Percentage->value, 'integer', 'min:0', 'max:10000000000'],
            'min_minor' => $money,
            'max_minor' => $this->filled('min_minor') ? [...$money, 'gte:min_minor'] : $money,
            'commission_on_tips' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('admin.commission_rules.fields.name'),
            'service_category_id' => __('admin.commission_rules.fields.service'),
            'service_zone_id' => __('admin.commission_rules.fields.zone'),
            'provider_tier' => __('admin.commission_rules.fields.provider_tier'),
            'campaign_code' => __('admin.commission_rules.fields.campaign'),
            'priority' => __('admin.commission_rules.fields.priority'),
            'effective_from' => __('admin.commission_rules.fields.effective_from'),
            'effective_to' => __('admin.commission_rules.fields.effective_to'),
            'type' => __('admin.commission_rules.fields.type'),
            'percent' => __('admin.commission_rules.fields.percent'),
            'fixed_minor' => __('admin.commission_rules.fields.fixed'),
            'min_minor' => __('admin.commission_rules.fields.min'),
            'max_minor' => __('admin.commission_rules.fields.max'),
            'commission_on_tips' => __('admin.commission_rules.fields.on_tips'),
            'notes' => __('admin.commission_rules.fields.notes'),
            'reason' => __('admin.commission_rules.fields.change_reason'),
        ];
    }

    public function toDto(): CommissionRuleData
    {
        return CommissionRuleData::fromRequest($this, (string) app(SettingsService::class)->string(SettingKey::DefaultCurrency));
    }
}
