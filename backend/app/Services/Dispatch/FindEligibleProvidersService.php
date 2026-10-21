<?php

namespace App\Services\Dispatch;

use App\DTOs\Dispatch\DispatchableRequest;
use App\DTOs\Dispatch\EligibleProvider;
use App\Enums\Provider\EligibilityScope;
use App\Enums\Settings\SettingKey;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\Vehicle;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Location\Index\ProviderGeoIndex;
use App\Services\Provider\ProviderEligibilityService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Who may be sent a request (SRS §10.1): a geo search around the pickup (fresh positions in the pickup's city), then
 * every candidate goes through the single `ProviderEligibilityService` with `EligibilityScope::Working` (approval,
 * documents, service, fresh location, single-active-job, debt limit) and the request's own needs (vehicle category,
 * passenger capacity). Providers already notified of this request in an earlier wave are skipped.
 */
final class FindEligibleProvidersService
{
    public function __construct(
        private readonly ProviderGeoIndex $index,
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly DispatchRecipientRepositoryInterface $recipients,
        private readonly ProviderEligibilityService $eligibility,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return list<EligibleProvider> nearest first, at most `$limit`
     */
    public function find(DispatchableRequest $request, float $radiusKm, int $limit): array
    {
        $category = $this->categories->find($request->serviceCategoryId);

        if (! $category instanceof ServiceCategory) {
            return [];
        }

        $excluded = array_flip($this->recipients->providerIdsFor($request->bookingId));
        $freshSince = CarbonImmutable::now()->subSeconds($this->settings->int(SettingKey::LocationStaleAfterSeconds));
        $hits = $this->index->nearby($request->cityId, $request->pickup, $radiusKm * 1000, $freshSince, count($excluded) + $limit * 4);
        $hits = array_values(array_filter($hits, static fn ($hit): bool => ! isset($excluded[$hit->position->providerId])));

        if ($hits === []) {
            return [];
        }

        $profiles = $this->profiles->findManyForDispatch(array_map(static fn ($hit): int => $hit->position->providerId, $hits))->keyBy('id');
        $found = [];

        foreach ($hits as $hit) {
            $profile = $profiles->get($hit->position->providerId);

            if (! $profile instanceof ProviderProfile || ! $profile->is_online) {
                continue;
            }

            $vehicle = $this->usableVehicle($profile, $category, $request);

            if ($vehicle === null) {
                continue;
            }

            $found[] = new EligibleProvider($profile, $vehicle->id, (int) round($hit->distanceMeters));

            if (count($found) >= $limit) {
                break;
            }
        }

        return $found;
    }

    private function usableVehicle(ProviderProfile $profile, ServiceCategory $category, DispatchableRequest $request): ?Vehicle
    {
        $result = $this->eligibility->check($profile, $category, EligibilityScope::Working);

        if (! $result->isEligible() || ! in_array($profile->current_vehicle_id, $result->usableVehicleIds, true)) {
            return null;
        }

        $vehicle = $this->vehicles->find((int) $profile->current_vehicle_id);

        if (! $vehicle instanceof Vehicle) {
            return null;
        }

        if ($request->vehicleCategoryIds !== [] && ! in_array($vehicle->vehicle_category_id, $request->vehicleCategoryIds, true)) {
            return null;
        }

        if ($request->passengerCount !== null && $vehicle->passenger_capacity !== null && $vehicle->passenger_capacity < $request->passengerCount) {
            return null;
        }

        return $vehicle;
    }
}
