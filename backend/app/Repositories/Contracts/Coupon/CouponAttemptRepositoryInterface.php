<?php

namespace App\Repositories\Contracts\Coupon;

use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\CouponAttempt;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Append-only log of coupon attempts (no update or delete).
 *
 * @extends BaseRepositoryInterface<CouponAttempt>
 */
interface CouponAttemptRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function record(array $attributes): CouponAttempt;

    /**
     * @return Builder<CouponAttempt>
     */
    public function datatableQuery(CouponActivityFilterData $filters): Builder;

    /**
     * @return array{total: int, rejected: int}
     */
    public function counts(int $couponId): array;

    /**
     * @return array<string, int> reason code => count of rejected attempts
     */
    public function rejectionsByReason(int $couponId): array;
}
