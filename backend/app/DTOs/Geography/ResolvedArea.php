<?php

namespace App\DTOs\Geography;

use App\Models\City;
use App\Models\ServiceZone;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Result of resolving a position: the serving city/zone (the smallest active zone that contains it) and the
 * categories that zone offers, each with its availability right now. Outside every active zone `zone` is null.
 */
final readonly class ResolvedArea
{
    /**
     * @param Collection<int, ServiceZone> $zones every active zone containing the point, smallest first
     * @param list<CategoryAvailability> $categories
     */
    public function __construct(
        public GeoPoint $point,
        public ?City $city,
        public ?ServiceZone $zone,
        public Collection $zones,
        public array $categories,
        public ?CarbonInterface $localTime,
    ) {}

    public function inServiceArea(): bool
    {
        return $this->zone !== null;
    }

    public function restrictedPickup(): bool
    {
        return $this->zone !== null && $this->zone->restricted_pickup;
    }

    /**
     * @return list<CategoryAvailability>
     */
    public function availableCategories(): array
    {
        return array_values(array_filter($this->categories, static fn (CategoryAvailability $c): bool => $c->isAvailable()));
    }

    public function availability(int $serviceCategoryId): ?CategoryAvailability
    {
        foreach ($this->categories as $availability) {
            if ($availability->category->id === $serviceCategoryId) {
                return $availability;
            }
        }

        return null;
    }
}
