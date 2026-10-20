<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\City;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;

/**
 * Cities for remote selects. City names are public reference data (the public API lists them), so any signed-in
 * admin may use the lookup: the Customers filters card needs it without `service_areas.manage`.
 */
final class CityLookup implements Lookup
{
    public function __construct(private readonly CityRepositoryInterface $cities) {}

    public function permission(): ?AdminPermission
    {
        return null;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->cities->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->cities->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, City> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(
            static fn (City $city): LookupOptionData => new LookupOptionData($city->id, (string) $city->name, ['timezone' => $city->timezone]),
            $items,
        ));
    }
}
