<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;

/**
 * Service zones for remote selects (names only; zone names are public reference data, any signed-in admin may use it).
 */
final class ZoneLookup implements Lookup
{
    public function __construct(private readonly ServiceZoneRepositoryInterface $zones) {}

    public function permission(): ?AdminPermission
    {
        return null;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->zones->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->zones->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, ServiceZone> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(
            static fn (ServiceZone $zone): LookupOptionData => new LookupOptionData($zone->id, (string) $zone->name, ['city_id' => $zone->city_id]),
            $items,
        ));
    }
}
