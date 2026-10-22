<?php

namespace App\Http\Requests\Api\V1\Coupon;

use App\DTOs\Coupon\CouponQuoteData;
use App\Enums\Payments\PaymentMethodType;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /coupons/validate: a quote of what a coupon would do for a prospective booking. Carries the customer's choices only;
 * any discount or total in the body is ignored (Plan I12).
 */
class ValidateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
            'service_category_id' => ['required', 'integer', 'min:1'],
            'proposed_fare_minor' => ['required', 'integer', 'min:1', 'max:9000000000000000'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethodType::class)],
            'pickup' => ['nullable', 'array'],
            'pickup.lat' => ['required_with:pickup', 'numeric', 'between:-90,90'],
            'pickup.lng' => ['required_with:pickup', 'numeric', 'between:-180,180'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function toDto(): CouponQuoteData
    {
        return new CouponQuoteData(
            code: (string) $this->input('coupon_code'),
            serviceCategoryId: (int) $this->input('service_category_id'),
            fareMinor: (int) $this->input('proposed_fare_minor'),
            paymentMethod: $this->filled('payment_method') ? PaymentMethodType::from((string) $this->input('payment_method')) : null,
            pickup: $this->filled('pickup.lat') ? GeoPoint::tryMake($this->input('pickup.lat'), $this->input('pickup.lng')) : null,
            at: $this->filled('scheduled_at') ? CarbonImmutable::parse((string) $this->input('scheduled_at')) : null,
        );
    }
}
