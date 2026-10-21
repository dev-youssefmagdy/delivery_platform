<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Booking\CancellationPolicyFilterData;
use App\Models\CancellationPolicy;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepositoryInterface<CancellationPolicy>
 */
interface CancellationPolicyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<CancellationPolicy>
     */
    public function datatableQuery(CancellationPolicyFilterData $filters): Builder;

    /**
     * The policy that governs a category at a moment: an enabled zone policy wins over the category policy, which wins
     * over a market-wide default (no category); among candidates the latest `effective_from` wins.
     */
    public function effectiveFor(int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $at): ?CancellationPolicy;

    /**
     * Enabled policies of exactly the same scope whose window intersects [from, to).
     *
     * @return list<CancellationPolicy>
     */
    public function overlapping(?int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $from, ?CarbonInterface $to, ?int $exceptId = null): array;

    /**
     * Bookings created under the policy.
     */
    public function bookingCount(CancellationPolicy $policy): int;
}
