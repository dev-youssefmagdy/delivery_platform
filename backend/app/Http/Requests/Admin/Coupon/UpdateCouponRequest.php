<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Models\Coupon;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Update a coupon. The code and currency are fixed after creation (customers hold the code). Changing the financial terms of a
 * live coupon adds a version and needs a reason (checked by `CouponService`); a stale form is refused with the lock version.
 */
class UpdateCouponRequest extends StoreCouponRequest
{
    public function authorize(): bool
    {
        $coupon = $this->route('coupon');

        return $coupon instanceof Coupon && Gate::allows('update', $coupon);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['code']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        $coupon = $this->route('coupon');

        return [function ($validator) use ($coupon): void {
            if ($coupon instanceof Coupon && $coupon->code === null && ! $this->filled('per_customer_limit')) {
                $validator->errors()->add('per_customer_limit', __('api.errors.coupon_automatic_needs_customer_limit'));
            }

            if ($this->input('funding_source') === 'split' && ! $validator->errors()->hasAny(['split_platform', 'split_provider', 'split_partner'])) {
                $sum = round((float) $this->input('split_platform') + (float) $this->input('split_provider') + (float) $this->input('split_partner'), 2);

                if ($sum !== 100.0) {
                    $validator->errors()->add('split_platform', __('api.errors.coupon_split_invalid'));
                }
            }
        }];
    }
}
