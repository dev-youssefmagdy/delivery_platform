<?php

namespace App\Repositories\Contracts\Pricing;

use App\DTOs\Pricing\FarePolicyFilterData;
use App\Models\FarePolicy;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepositoryInterface<FarePolicy>
 */
interface FarePolicyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<FarePolicy>
     */
    public function datatableQuery(FarePolicyFilterData $filters): Builder;

    /**
     * The policy that prices a category at a moment: an enabled zone policy wins over the category-wide one; among
     * several candidates the latest `effective_from` wins.
     */
    public function effectiveFor(int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $at): ?FarePolicy;

    /**
     * Enabled policies of the same scope (category + zone, or category-wide when the zone is null) whose window
     * intersects [from, to).
     *
     * @return list<FarePolicy>
     */
    public function overlapping(int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $from, ?CarbonInterface $to, ?int $exceptId = null): array;

    /**
     * Bookings that were priced with the policy.
     */
    public function bookingCount(FarePolicy $policy): int;
}
