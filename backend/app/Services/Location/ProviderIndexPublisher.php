<?php

namespace App\Services\Location;

use App\DTOs\Location\ProviderPosition;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Settings\SettingKey;
use App\Models\ProviderLastLocation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Location\Index\ProviderGeoIndex;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Keeps the geo index in step with the provider: adds the position with the categories the provider can serve (its
 * online vehicle and approved services), removes it when the provider stops being available.
 */
final class ProviderIndexPublisher
{
    public function __construct(
        private readonly ProviderGeoIndex $index,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderServiceRepositoryInterface $services,
        private readonly SettingsService $settings,
    ) {}

    public function publish(ProviderProfile $profile, ProviderLastLocation $location): void
    {
        if ($location->city_id === null || $profile->current_vehicle_id === null) {
            return;
        }

        $vehicle = $this->vehicles->find($profile->current_vehicle_id);

        $this->index->put(new ProviderPosition(
            providerId: $profile->id,
            cityId: $location->city_id,
            point: $location->point,
            recordedAt: CarbonImmutable::instance($location->recorded_at),
            accuracyM: $location->accuracy_m,
            heading: $location->heading,
            speedMps: $location->speed_mps,
            vehicleCategoryIds: $vehicle === null ? [] : [$vehicle->vehicle_category_id],
            serviceCategoryIds: $this->services->categoryIds($profile, [ServiceApprovalStatus::Approved]),
        ), $this->settings->int(SettingKey::LocationStaleAfterSeconds));
    }

    public function withdraw(ProviderProfile $profile): void
    {
        $this->index->remove($profile->id);
    }
}
