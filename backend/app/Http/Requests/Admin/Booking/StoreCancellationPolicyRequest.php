<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\CancellationPolicyData;
use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Booking\CancellationFeeType;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;
use App\Enums\Settings\SettingKey;
use App\Models\CancellationPolicy;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a cancellation policy. Fee amounts are typed in major units inside the rules repeater (the DTO converts them);
 * overlap and rule sanity are checked again by `CancellationPolicyService` under lock.
 */
class StoreCancellationPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', CancellationPolicy::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'service_category_id' => ['nullable', 'integer', Rule::exists('service_categories', 'id')],
            'service_zone_id' => ['nullable', 'integer', Rule::exists('service_zones', 'id')],
            'free_grace_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'free_wait_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'effective_from' => ['required', 'date_format:Y-m-d H:i'],
            'effective_to' => ['nullable', 'date_format:Y-m-d H:i', 'after:effective_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'customer_reasons' => ['required', 'array', 'min:1'],
            'customer_reasons.*' => ['string', Rule::in(CancellationReason::valuesForActor(ActorType::Customer))],
            'provider_reasons' => ['required', 'array', 'min:1'],
            'provider_reasons.*' => ['string', Rule::in(CancellationReason::valuesForActor(ActorType::Provider))],
            'rules' => ['nullable', 'array', 'max:12'],
            'rules.*.actor' => ['nullable', Rule::in([ActorType::Customer->value, ActorType::Provider->value])],
            'rules.*.stage' => ['nullable', Rule::in(array_map(static fn (CancellationStage $stage): string => $stage->value, CancellationStage::chargeable()))],
            'rules.*.fee_type' => ['nullable', Rule::enum(CancellationFeeType::class)],
            'rules.*.amount' => ['nullable', 'string', 'regex:/^\d{1,12}(\.\d{1,4})?$/'],
            'rules.*.percent' => ['nullable', 'string', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'rules.*.cap' => ['nullable', 'string', 'regex:/^\d{1,12}(\.\d{1,4})?$/'],
            'rules.*.recipient' => ['nullable', Rule::enum(CancellationFeeRecipient::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('admin.cancellation_policies.fields.name'),
            'service_category_id' => __('admin.cancellation_policies.fields.service'),
            'service_zone_id' => __('admin.cancellation_policies.fields.zone'),
            'free_grace_seconds' => __('admin.cancellation_policies.fields.free_grace'),
            'free_wait_seconds' => __('admin.cancellation_policies.fields.free_wait'),
            'effective_from' => __('admin.cancellation_policies.fields.effective_from'),
            'effective_to' => __('admin.cancellation_policies.fields.effective_to'),
            'notes' => __('admin.cancellation_policies.fields.notes'),
            'customer_reasons' => __('admin.cancellation_policies.fields.customer_reasons'),
            'provider_reasons' => __('admin.cancellation_policies.fields.provider_reasons'),
            'rules.*.amount' => __('admin.cancellation_policies.rules.amount'),
            'rules.*.percent' => __('admin.cancellation_policies.rules.percent'),
            'rules.*.cap' => __('admin.cancellation_policies.rules.cap'),
        ];
    }

    public function toDto(): CancellationPolicyData
    {
        $currency = (string) app(SettingsService::class)->string(SettingKey::DefaultCurrency);

        return CancellationPolicyData::fromRequest($this, app(CurrencyRegistry::class)->get($currency)->exponent);
    }
}
