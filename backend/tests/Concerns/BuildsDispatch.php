<?php

namespace Tests\Concerns;

use App\DTOs\Dispatch\DispatchableRequest;
use App\DTOs\Location\ActiveBooking;
use App\DTOs\Location\LocationSample;
use App\Enums\Location\LocationSource;
use App\Enums\Location\TrackingState;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\City;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;
use App\Services\Provider\AvailabilityService;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;

/**
 * Fixtures for availability, location and dispatch tests. The default zone is the Cairo square of BuildsGeography
 * (30.0..30.1 N, 31.2..31.3 E); its centre is (30.05, 31.25).
 */
trait BuildsDispatch
{
    use BuildsProviders;

    /**
     * @return array{category: ServiceCategory, zone: ServiceZone, city: City}
     */
    protected function cairo(): array
    {
        $category = $this->bookableCategory();
        $zone = $this->zoneOffering([$category]);

        return ['category' => $category, 'zone' => $zone, 'city' => $zone->city];
    }

    protected function sampleAt(float $lat = 30.05, float $lng = 31.25, ?CarbonImmutable $at = null, array $extra = []): LocationSample
    {
        return new LocationSample(
            point: GeoPoint::make($lat, $lng),
            recordedAt: $at ?? CarbonImmutable::now(),
            accuracyM: $extra['accuracy'] ?? 8.0,
            heading: $extra['heading'] ?? null,
            speedMps: $extra['speed'] ?? null,
            source: LocationSource::Gps,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function samplePayload(float $lat = 30.05, float $lng = 31.25, ?CarbonImmutable $at = null): array
    {
        return ['lat' => $lat, 'lng' => $lng, 'recorded_at' => ($at ?? CarbonImmutable::now())->toIso8601String(), 'accuracy_m' => 8, 'source' => 'gps'];
    }

    /**
     * A fully approved provider of the category with an approved vehicle of a category the service accepts.
     */
    protected function approvedProviderFor(ServiceCategory $category, ?City $city = null): ProviderProfile
    {
        $provider = $this->makeProvider(ProviderStatus::Approved, ['city_id' => $city?->id]);
        $this->requestService($provider, $category, ServiceApprovalStatus::Approved);
        $this->vehicleFor($provider, VehicleStatus::Approved, ['vehicle_category_id' => $category->vehicleCategories()->firstOrFail()->id]);

        return $provider->refresh();
    }

    /**
     * An approved provider taken online through the real service at the given position.
     */
    protected function onlineProviderFor(ServiceCategory $category, float $lat = 30.05, float $lng = 31.25, ?City $city = null): ProviderProfile
    {
        $provider = $this->approvedProviderFor($category, $city);
        app(AvailabilityService::class)->goOnline($provider, null, $this->sampleAt($lat, $lng));

        return $provider->refresh();
    }

    protected function requestFor(ServiceCategory $category, City $city, int $bookingId = 501, ?CarbonImmutable $expires = null, array $overrides = []): DispatchableRequest
    {
        return new DispatchableRequest(
            bookingId: $bookingId,
            bookingUuid: $overrides['uuid'] ?? '11111111-1111-4111-8111-'.str_pad((string) $bookingId, 12, '0', STR_PAD_LEFT),
            serviceCategoryId: $category->id,
            cityId: $city->id,
            pickup: GeoPoint::make(30.05, 31.25),
            searchExpiresAt: $expires ?? CarbonImmutable::now()->addMinutes(5),
            searching: $overrides['searching'] ?? true,
            vehicleCategoryIds: $overrides['vehicle_category_ids'] ?? [],
            passengerCount: $overrides['passengers'] ?? null,
            summary: ['service' => 'ride', 'fare_minor' => 5000],
        );
    }

    /**
     * Makes every provider look busy (or not) until P2.3 provides real bookings.
     */
    protected function fakeWorkload(?ActiveBooking $active = null, int $debtMinor = 0): void
    {
        $this->app->instance(ProviderWorkloadRepositoryInterface::class, new class($active, $debtMinor) implements ProviderWorkloadRepositoryInterface
        {
            public function __construct(private readonly ?ActiveBooking $active, private readonly int $debt) {}

            public function activeBooking(ProviderProfile $profile): ?ActiveBooking
            {
                return $this->active;
            }

            public function cashDebtMinor(ProviderProfile $profile): int
            {
                return $this->debt;
            }
        });
    }

    protected function busy(string $uuid = '22222222-2222-4222-8222-222222222222'): ActiveBooking
    {
        return new ActiveBooking(900, $uuid, TrackingState::Active);
    }
}
