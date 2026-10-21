<?php

namespace App\Http\Requests\Admin\Pricing;

use App\DTOs\Pricing\FarePolicyData;
use App\Enums\Pricing\FareFormula;
use App\Enums\Pricing\SurchargeMode;
use App\Enums\Settings\SettingKey;
use App\Models\FarePolicy;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a fare policy. Amounts arrive in minor units (the money input submits them that way); surcharge amounts are
 * typed in major units inside the repeater and converted by `FarePolicyData`. Scope and business rules (overlap, ranges)
 * are checked again by `FarePolicyService` under lock.
 */
class StoreFarePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', FarePolicy::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $money = ['required', 'integer', 'min:0', 'max:10000000000'];

        return [
            'service_category_id' => ['required', 'integer', Rule::exists('service_categories', 'id')],
            'service_zone_id' => ['nullable', 'integer', Rule::exists('service_zones', 'id')],
            'formula_version' => ['required', 'integer', Rule::enum(FareFormula::class)],
            'base_minor' => $money,
            'per_km_minor' => $money,
            'per_min_minor' => $money,
            'min_fare_minor' => $money,
            'stop_fee_minor' => $money,
            'rounding_step_minor' => ['required', 'integer', 'min:1', 'max:100000'],
            'range_low_pct' => ['required', 'numeric', 'min:1', 'max:100'],
            'range_high_pct' => ['required', 'numeric', 'min:100', 'max:10000'],
            'effective_from' => ['required', 'date_format:Y-m-d H:i'],
            'effective_to' => ['nullable', 'date_format:Y-m-d H:i', 'after:effective_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'surcharges' => ['nullable', 'array', 'max:20'],
            'surcharges.*.field' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
            'surcharges.*.mode' => ['nullable', Rule::enum(SurchargeMode::class)],
            'surcharges.*.amount' => ['nullable', 'string', 'regex:/^\d{1,12}(\.\d{1,4})?$/'],
            'surcharges.*.match' => ['nullable', 'string', 'max:100'],
            'surcharges.*.label_ar' => ['nullable', 'string', 'max:100'],
            'surcharges.*.label_en' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_category_id' => __('admin.fare_policies.fields.service'),
            'service_zone_id' => __('admin.fare_policies.fields.zone'),
            'formula_version' => __('admin.fare_policies.fields.formula'),
            'base_minor' => __('admin.fare_policies.fields.base'),
            'per_km_minor' => __('admin.fare_policies.fields.per_km'),
            'per_min_minor' => __('admin.fare_policies.fields.per_min'),
            'min_fare_minor' => __('admin.fare_policies.fields.min_fare'),
            'stop_fee_minor' => __('admin.fare_policies.fields.stop_fee'),
            'rounding_step_minor' => __('admin.fare_policies.fields.rounding_step'),
            'range_low_pct' => __('admin.fare_policies.fields.range_low'),
            'range_high_pct' => __('admin.fare_policies.fields.range_high'),
            'effective_from' => __('admin.fare_policies.fields.effective_from'),
            'effective_to' => __('admin.fare_policies.fields.effective_to'),
            'notes' => __('admin.fare_policies.fields.notes'),
            'surcharges.*.field' => __('admin.fare_policies.surcharges.field'),
            'surcharges.*.amount' => __('admin.fare_policies.surcharges.amount'),
        ];
    }

    public function toDto(): FarePolicyData
    {
        $currency = (string) app(SettingsService::class)->string(SettingKey::DefaultCurrency);

        return FarePolicyData::fromRequest($this, $currency, app(CurrencyRegistry::class)->get($currency)->exponent);
    }
}
