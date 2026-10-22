<?php

namespace App\Http\Requests\Admin\Coupon;

use App\DTOs\Coupon\CouponData;
use App\Enums\Catalog\Weekday;
use App\Enums\Coupon\CouponSegment;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Settings\SettingKey;
use App\Models\Coupon;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create a coupon. Amounts arrive in minor units (the money input submits them that way), percentages as percents. The
 * funding split must add up to 100%. Code uniqueness, the period and the value are repeated by `CouponService` under lock.
 */
class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Coupon::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => mb_strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $money = ['nullable', 'integer', 'min:0', 'max:10000000000'];
        $count = ['nullable', 'integer', 'min:1', 'max:100000000'];
        $ids = static fn (string $table): array => ['nullable', 'array', 'max:200'];

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'regex:/^[A-Z0-9_-]{3,40}$/', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && app(CouponRepositoryInterface::class)->codeExists($value, $this->existingId())) {
                    $fail(__('api.errors.coupon_code_taken'));
                }
            }],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:191'],
            'title.en' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'legal_terms' => ['nullable', 'array'],
            'legal_terms.ar' => ['nullable', 'string', 'max:4000'],
            'legal_terms.en' => ['nullable', 'string', 'max:4000'],

            'discount_type' => ['required', 'string', Rule::enum(DiscountType::class)],
            'percent' => ['nullable', 'required_if:discount_type,'.DiscountType::Percentage->value, 'numeric', 'gt:0', 'max:100'],
            'fixed_minor' => ['nullable', 'required_if:discount_type,'.DiscountType::Fixed->value, 'integer', 'min:1', 'max:10000000000'],
            'max_discount_minor' => $money,
            'min_fare_minor' => $money,
            'per_customer_limit' => $count,
            'global_limit' => $count,
            'budget_minor' => ['nullable', 'integer', 'min:1', 'max:1000000000000'],

            'funding_source' => ['required', 'string', Rule::enum(FundingSource::class)],
            'split_platform' => ['nullable', 'required_if:funding_source,'.FundingSource::Split->value, 'numeric', 'min:0', 'max:100'],
            'split_provider' => ['nullable', 'required_if:funding_source,'.FundingSource::Split->value, 'numeric', 'min:0', 'max:100'],
            'split_partner' => ['nullable', 'required_if:funding_source,'.FundingSource::Split->value, 'numeric', 'min:0', 'max:100'],
            'stackable' => ['nullable', 'boolean'],
            'restore_on_refund' => ['nullable', 'boolean'],

            'family_ids' => $ids('service_families'),
            'family_ids.*' => ['integer', Rule::exists('service_families', 'id')],
            'category_ids' => $ids('service_categories'),
            'category_ids.*' => ['integer', Rule::exists('service_categories', 'id')],
            'vehicle_category_ids' => $ids('vehicle_categories'),
            'vehicle_category_ids.*' => ['integer', Rule::exists('vehicle_categories', 'id')],
            'city_ids' => $ids('cities'),
            'city_ids.*' => ['integer', Rule::exists('cities', 'id')],
            'zone_ids' => $ids('service_zones'),
            'zone_ids.*' => ['integer', Rule::exists('service_zones', 'id')],
            'excluded_category_ids' => $ids('service_categories'),
            'excluded_category_ids.*' => ['integer', Rule::exists('service_categories', 'id')],
            'excluded_vehicle_category_ids' => $ids('vehicle_categories'),
            'excluded_vehicle_category_ids.*' => ['integer', Rule::exists('vehicle_categories', 'id')],
            'segments' => ['nullable', 'array', 'max:2'],
            'segments.*' => ['distinct', Rule::enum(CouponSegment::class)],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['distinct', Rule::enum(PaymentMethodType::class)],
            'schedule' => ['nullable', 'array', 'max:28'],
            'schedule.*.day' => ['required', Rule::enum(Weekday::class)],
            'schedule.*.from' => ['required', 'date_format:H:i'],
            'schedule.*.to' => ['required', 'date_format:H:i'],

            'timezone' => ['required', 'timezone:all'],
            'starts_at' => ['nullable', 'date_format:Y-m-d H:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d H:i', 'after:starts_at'],
            'priority' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('funding_source') === FundingSource::Split->value && ! $validator->errors()->hasAny(['split_platform', 'split_provider', 'split_partner'])) {
                $sum = round((float) $this->input('split_platform') + (float) $this->input('split_provider') + (float) $this->input('split_partner'), 2);

                if ($sum !== 100.0) {
                    $validator->errors()->add('split_platform', __('api.errors.coupon_split_invalid'));
                }
            }

            if (! $this->filled('code') && ! $this->filled('per_customer_limit')) {
                $validator->errors()->add('per_customer_limit', __('api.errors.coupon_automatic_needs_customer_limit'));
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $fields = ['name', 'code', 'title', 'description', 'legal_terms', 'discount_type', 'percent', 'fixed_minor', 'max_discount_minor', 'min_fare_minor',
            'per_customer_limit', 'global_limit', 'budget_minor', 'funding_source', 'split_platform', 'split_provider', 'split_partner', 'stackable',
            'restore_on_refund', 'family_ids', 'category_ids', 'vehicle_category_ids', 'city_ids', 'zone_ids', 'excluded_category_ids',
            'excluded_vehicle_category_ids', 'segments', 'payment_methods', 'schedule', 'timezone', 'starts_at', 'ends_at', 'priority'];
        $labels = [];

        foreach ($fields as $field) {
            $labels[$field] = __('admin.coupons.fields.'.$field);
        }

        return $labels + ['title.ar' => __('admin.coupons.fields.title'), 'reason' => __('admin.coupons.fields.change_reason')];
    }

    public function toDto(): CouponData
    {
        return CouponData::fromRequest($this, (string) app(SettingsService::class)->string(SettingKey::DefaultCurrency));
    }

    protected function existingId(): ?int
    {
        $coupon = $this->route('coupon');

        return $coupon instanceof Coupon ? $coupon->id : null;
    }
}
