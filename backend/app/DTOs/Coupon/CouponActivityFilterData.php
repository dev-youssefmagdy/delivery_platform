<?php

namespace App\DTOs\Coupon;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Coupon\CouponAttemptResult;
use App\Enums\Coupon\CouponRedemptionStatus;
use App\Enums\Coupon\CouponReservationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the coupon tabs (redemptions, attempts) and of a customer's coupon tab: status / result and a date range,
 * plus the scope the tab is narrowed to (set by the DataTable from its route, never from the request).
 */
final readonly class CouponActivityFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<CouponRedemptionStatus> $redemptionStatuses
     * @param list<CouponAttemptResult> $attemptResults
     * @param list<CouponReservationStatus> $reservationStatuses
     */
    public function __construct(
        public array $redemptionStatuses = [],
        public array $attemptResults = [],
        public array $reservationStatuses = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $couponId = null,
        public ?int $userId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'date_range');

        return new self(
            redemptionStatuses: self::enumListFilter($input, 'redemption_statuses', CouponRedemptionStatus::class),
            attemptResults: self::enumListFilter($input, 'attempt_results', CouponAttemptResult::class),
            reservationStatuses: self::enumListFilter($input, 'reservation_statuses', CouponReservationStatus::class),
            from: $from,
            to: $to,
        );
    }

    public function forCoupon(int $couponId): self
    {
        return new self($this->redemptionStatuses, $this->attemptResults, $this->reservationStatuses, $this->from, $this->to, $couponId, $this->userId);
    }

    public function forUser(int $userId): self
    {
        return new self($this->redemptionStatuses, $this->attemptResults, $this->reservationStatuses, $this->from, $this->to, $this->couponId, $userId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->redemptionStatuses, $this->attemptResults, $this->reservationStatuses, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'redemption_statuses' => array_map(static fn (CouponRedemptionStatus $status): string => $status->value, $this->redemptionStatuses),
            'attempt_results' => array_map(static fn (CouponAttemptResult $result): string => $result->value, $this->attemptResults),
            'reservation_statuses' => array_map(static fn (CouponReservationStatus $status): string => $status->value, $this->reservationStatuses),
            'date_range' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
