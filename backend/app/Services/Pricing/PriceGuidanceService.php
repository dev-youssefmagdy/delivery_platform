<?php

namespace App\Services\Pricing;

use App\DTOs\Geography\RouteData;
use App\DTOs\Pricing\FarePolicyValues;
use App\DTOs\Pricing\PriceGuidanceResult;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Pricing\FarePolicyRepositoryInterface;
use Carbon\CarbonInterface;

/**
 * Finds the fare policy in effect for a category / zone / moment and turns a route into price guidance. Returns the
 * reason when there is none instead of inventing a number.
 */
final class PriceGuidanceService
{
    public function __construct(
        private readonly FarePolicyRepositoryInterface $policies,
        private readonly ReferencePriceCalculator $calculator,
    ) {}

    /**
     * @param array<string, mixed> $serviceDetails
     */
    public function guide(ServiceCategory $category, ?int $zoneId, RouteData $route, int $stopCount, array $serviceDetails, CarbonInterface $at): PriceGuidanceResult
    {
        return $this->guideWith($category, $this->policyFor($category, $zoneId, $at), $route, $stopCount, $serviceDetails);
    }

    /**
     * Guidance from an already resolved policy (booking creation resolves it once and stores it).
     *
     * @param array<string, mixed> $serviceDetails
     */
    public function guideWith(ServiceCategory $category, ?FarePolicy $policy, RouteData $route, int $stopCount, array $serviceDetails): PriceGuidanceResult
    {
        if (! $category->reference_estimate_enabled) {
            return PriceGuidanceResult::unavailable(PriceGuidanceResult::DISABLED);
        }

        if (! $policy instanceof FarePolicy) {
            return PriceGuidanceResult::unavailable(PriceGuidanceResult::NO_POLICY);
        }

        return PriceGuidanceResult::of($this->calculator->calculate(
            FarePolicyValues::fromModel($policy),
            $route->distanceMeters,
            $route->durationSeconds,
            $stopCount,
            $serviceDetails,
            $policy->id,
        ));
    }

    /**
     * The enabled policy that prices the category in the zone at that moment.
     */
    public function policyFor(ServiceCategory $category, ?int $zoneId, CarbonInterface $at): ?FarePolicy
    {
        return $category->reference_estimate_enabled ? $this->policies->effectiveFor($category->id, $zoneId, $at) : null;
    }
}
