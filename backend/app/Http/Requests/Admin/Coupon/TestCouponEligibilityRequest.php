<?php

namespace App\Http\Requests\Admin\Coupon;

use App\DTOs\Coupon\CouponQuoteData;
use App\Enums\Payments\PaymentMethodType;
use App\Models\Coupon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The "test eligibility" tool of the coupon page: a customer, a service, a fare, a payment method, a place and a time. Read-only:
 * it evaluates the rules and logs an `admin_test` attempt, it reserves nothing.
 */
class TestCouponEligibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coupon = $this->route('coupon');

        return $coupon instanceof Coupon && Gate::allows('view', $coupon);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'service_category_id' => ['required', 'integer', Rule::exists('service_categories', 'id')],
            'fare_minor' => ['required', 'integer', 'min:1', 'max:10000000000'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethodType::class)],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('service_zones', 'id')],
            'at' => ['nullable', 'date_format:Y-m-d H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => __('admin.coupons.test.customer'),
            'service_category_id' => __('admin.coupons.test.service'),
            'fare_minor' => __('admin.coupons.test.fare'),
            'payment_method' => __('admin.coupons.test.payment_method'),
            'city_id' => __('admin.coupons.test.city'),
            'zone_id' => __('admin.coupons.test.zone'),
            'at' => __('admin.coupons.test.at'),
        ];
    }

    public function customerId(): int
    {
        return (int) $this->validated('customer_id');
    }

    public function toDto(): CouponQuoteData
    {
        $timezone = (string) config('admin.display_timezone', 'UTC');

        return new CouponQuoteData(
            code: null,
            serviceCategoryId: (int) $this->validated('service_category_id'),
            fareMinor: (int) $this->validated('fare_minor'),
            paymentMethod: $this->filled('payment_method') ? PaymentMethodType::from((string) $this->input('payment_method')) : null,
            pickup: null,
            cityId: $this->filled('city_id') ? (int) $this->input('city_id') : null,
            zoneId: $this->filled('zone_id') ? (int) $this->input('zone_id') : null,
            at: $this->filled('at') ? CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $this->input('at'), $timezone)->utc() : null,
        );
    }
}
