<?php

namespace App\Services\Coupon;

use App\Enums\Coupon\CouponAttemptContext;
use App\Enums\Coupon\CouponAttemptResult;
use App\Enums\Coupon\CouponReason;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;

/**
 * Writes the append-only log of coupon attempts (SRS §11.2 step 8). Never throws into the caller's flow for a logging problem.
 */
final class CouponAttemptRecorder
{
    public function __construct(private readonly CouponAttemptRepositoryInterface $attempts) {}

    /**
     * @param array<string, mixed> $meta
     */
    public function record(
        CouponAttemptContext $context,
        CouponAttemptResult $result,
        CouponReason $reason,
        ?int $couponId,
        ?string $codeEntered,
        ?int $userId,
        ?int $serviceCategoryId = null,
        ?int $bookingId = null,
        ?int $discountMinor = null,
        array $meta = [],
    ): void {
        $this->attempts->record([
            'coupon_id' => $couponId,
            'code_entered' => $codeEntered === null ? null : mb_substr(trim($codeEntered), 0, 64),
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'service_category_id' => $serviceCategoryId,
            'context' => $context,
            'result' => $result,
            'reason_code' => $reason->value,
            'discount_minor' => $discountMinor,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
