<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Booking\CancellationPreviewData;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;
use App\Enums\Settings\SettingKey;
use App\Models\CancellationPolicy;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The "try it" calculator on the policy page. Read-only: it only computes what the policy would charge.
 */
class PreviewCancellationPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policy = $this->route('cancellationPolicy');

        return $policy instanceof CancellationPolicy && Gate::allows('view', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actor' => ['required', Rule::in([ActorType::Customer->value, ActorType::Provider->value])],
            'stage' => ['required', Rule::in([CancellationStage::ProviderSelected->value, CancellationStage::HeadingToPickup->value, CancellationStage::Arrived->value])],
            'reason_code' => ['nullable', Rule::enum(CancellationReason::class)],
            'fare_minor' => ['required', 'integer', 'min:0', 'max:10000000000'],
            'minutes_since_selection' => ['required', 'integer', 'min:0', 'max:1440'],
            'minutes_since_arrival' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'arrival_verified' => ['nullable', 'boolean'],
            'report_verified' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'actor' => __('admin.cancellation_policies.preview.actor'),
            'stage' => __('admin.cancellation_policies.preview.stage'),
            'reason_code' => __('admin.cancellation_policies.preview.reason'),
            'fare_minor' => __('admin.cancellation_policies.preview.fare'),
            'minutes_since_selection' => __('admin.cancellation_policies.preview.since_selection'),
            'minutes_since_arrival' => __('admin.cancellation_policies.preview.since_arrival'),
        ];
    }

    public function toDto(): CancellationPreviewData
    {
        return new CancellationPreviewData(
            actor: ActorType::from((string) $this->input('actor')),
            stage: CancellationStage::from((string) $this->input('stage')),
            reason: $this->filled('reason_code') ? CancellationReason::from((string) $this->input('reason_code')) : null,
            fareMinor: $this->integer('fare_minor'),
            minutesSinceSelection: $this->integer('minutes_since_selection'),
            minutesSinceArrival: $this->integer('minutes_since_arrival'),
            arrivalVerified: $this->boolean('arrival_verified'),
            reportVerified: $this->boolean('report_verified'),
            currency: (string) app(SettingsService::class)->string(SettingKey::DefaultCurrency),
        );
    }
}
