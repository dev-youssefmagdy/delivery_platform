<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\BookingType;
use App\Enums\Catalog\ProofType;
use App\Enums\Catalog\Weekday;
use App\Enums\Common\Locale;
use App\Enums\Payments\PaymentMethodType;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ServiceCategory::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtolower(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_family_id' => ['required', 'integer', Rule::exists('service_families', 'id')],
            'code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,39}$/', Rule::unique('service_categories', 'code')],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'map_marker' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],

            'allowed_booking_types' => ['required', 'array', 'min:1'],
            'allowed_booking_types.*' => ['distinct', Rule::enum(BookingType::class)],
            'supports_stops' => ['required', 'boolean'],
            'max_stops' => ['nullable', 'required_if_accepted:supports_stops', 'integer', 'min:1', 'max:20'],
            'service_hours' => ['nullable', 'array', 'max:28'],
            'service_hours.*.day' => ['required', Rule::enum(Weekday::class)],
            'service_hours.*.from' => ['required', 'date_format:H:i'],
            'service_hours.*.to' => ['required', 'date_format:H:i', 'after:service_hours.*.from'],

            'negotiation_enabled' => ['required', 'boolean'],
            'reference_estimate_enabled' => ['required', 'boolean'],
            'min_customer_fare_minor' => ['nullable', 'integer', 'min:0'],
            'max_customer_fare_minor' => ['nullable', 'integer', 'min:0', 'gte:min_customer_fare_minor'],
            'counter_offer_min_pct' => ['nullable', 'integer', 'min:1', 'max:100'],
            'counter_offer_max_pct' => ['nullable', 'integer', 'min:100', 'max:1000'],

            'offer_ttl_seconds' => ['nullable', 'integer', 'min:10', 'max:1800'],
            'search_ttl_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
            'dispatch_radius_steps_km' => ['nullable', 'string', 'max:100', 'regex:/^\s*\d+(\.\d+)?(\s*,\s*\d+(\.\d+)?){0,7}\s*$/'],
            'dispatch_batch_size' => ['nullable', 'integer', 'min:1', 'max:200'],

            'max_passengers' => ['nullable', 'integer', 'min:1', 'max:100'],
            'max_luggage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_weight_kg' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'max_volume_m3' => ['nullable', 'numeric', 'gt:0', 'max:100000'],

            'pin_required' => ['nullable', 'in:0,1'],
            'pickup_proof_required' => ['required', 'boolean'],
            'delivery_proof_types' => ['nullable', 'array'],
            'delivery_proof_types.*' => ['distinct', Rule::enum(ProofType::class)],
            'recipient_otp_required' => ['required', 'boolean'],

            'tipping_enabled' => ['required', 'boolean'],
            'coupon_eligible' => ['required', 'boolean'],
            'cash_allowed' => ['required', 'boolean'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['distinct', Rule::enum(PaymentMethodType::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $methods = (array) $this->input('payment_methods', []);
            $cashAllowed = filter_var($this->input('cash_allowed'), FILTER_VALIDATE_BOOLEAN);

            if (! $validator->errors()->has('payment_methods')) {
                if ($cashAllowed && ! in_array(PaymentMethodType::Cash->value, $methods, true)) {
                    $validator->errors()->add('payment_methods', __('admin.service_categories.errors.cash_method_required'));
                } elseif (! $cashAllowed && in_array(PaymentMethodType::Cash->value, $methods, true)) {
                    $validator->errors()->add('payment_methods', __('admin.service_categories.errors.cash_method_forbidden'));
                }
            }

            $steps = $this->input('dispatch_radius_steps_km');

            if (is_string($steps) && trim($steps) !== '' && ! $validator->errors()->has('dispatch_radius_steps_km')) {
                $values = array_map(static fn (string $part): float => (float) trim($part), explode(',', $steps));

                if (min($values) <= 0 || max($values) > 500 || $values !== collect($values)->sort()->values()->all()) {
                    $validator->errors()->add('dispatch_radius_steps_km', __('admin.service_categories.errors.radius_steps'));
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $f = static fn (string $key): string => (string) __("admin.service_categories.fields.$key");
        $name = $f('name');
        $description = $f('description');

        return [
            'service_family_id' => $f('family'),
            'code' => __('admin.catalog.fields.code'),
            'name' => $name,
            'name.ar' => $name.' ('.Locale::Arabic->label().')',
            'name.en' => $name.' ('.Locale::English->label().')',
            'description.ar' => $description.' ('.Locale::Arabic->label().')',
            'description.en' => $description.' ('.Locale::English->label().')',
            'icon' => __('admin.catalog.fields.icon'),
            'map_marker' => __('admin.catalog.fields.map_marker'),
            'sort_order' => __('admin.catalog.fields.sort_order'),
            'allowed_booking_types' => $f('allowed_booking_types'),
            'supports_stops' => $f('supports_stops'),
            'max_stops' => $f('max_stops'),
            'service_hours' => $f('service_hours'),
            'service_hours.*.day' => $f('hours_day'),
            'service_hours.*.from' => $f('hours_from'),
            'service_hours.*.to' => $f('hours_to'),
            'negotiation_enabled' => $f('negotiation_enabled'),
            'reference_estimate_enabled' => $f('reference_estimate_enabled'),
            'min_customer_fare_minor' => $f('min_customer_fare'),
            'max_customer_fare_minor' => $f('max_customer_fare'),
            'counter_offer_min_pct' => $f('counter_offer_min_pct'),
            'counter_offer_max_pct' => $f('counter_offer_max_pct'),
            'offer_ttl_seconds' => $f('offer_ttl_seconds'),
            'search_ttl_seconds' => $f('search_ttl_seconds'),
            'dispatch_radius_steps_km' => $f('dispatch_radius_steps_km'),
            'dispatch_batch_size' => $f('dispatch_batch_size'),
            'max_passengers' => $f('max_passengers'),
            'max_luggage' => $f('max_luggage'),
            'max_weight_kg' => $f('max_weight_kg'),
            'max_volume_m3' => $f('max_volume_m3'),
            'pin_required' => $f('pin_required'),
            'pickup_proof_required' => $f('pickup_proof_required'),
            'delivery_proof_types' => $f('delivery_proof_types'),
            'recipient_otp_required' => $f('recipient_otp_required'),
            'tipping_enabled' => $f('tipping_enabled'),
            'coupon_eligible' => $f('coupon_eligible'),
            'cash_allowed' => $f('cash_allowed'),
            'payment_methods' => $f('payment_methods'),
        ];
    }
}
