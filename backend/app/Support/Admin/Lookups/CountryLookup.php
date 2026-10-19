<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\Country;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;

/**
 * Countries (reference data, non-sensitive).
 */
final class CountryLookup implements Lookup
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    public function permission(): ?AdminPermission
    {
        return null;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->countries->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->countries->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, Country> $countries
     * @return list<LookupOptionData>
     */
    private function map(array $countries): array
    {
        return array_values(array_map(
            static fn (Country $country): LookupOptionData => new LookupOptionData($country->id, $country->name.' ('.$country->iso2.')', ['iso2' => $country->iso2]),
            $countries,
        ));
    }
}
