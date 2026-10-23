<?php

namespace App\Support\Growth;

use App\Models\City;
use App\Models\ServiceCategory;

/**
 * Display labels of the saved city / service category ids of a campaign, for the remote-select fields of the admin forms.
 */
final class ScopeOptions
{
    /**
     * @param list<int>|null $ids
     * @return array<int, string>
     */
    public function cities(?array $ids): array
    {
        return $this->labels(City::class, $ids);
    }

    /**
     * @param list<int>|null $ids
     * @return array<int, string>
     */
    public function categories(?array $ids): array
    {
        return $this->labels(ServiceCategory::class, $ids);
    }

    /**
     * @param class-string<City|ServiceCategory> $model
     * @param list<int>|null $ids
     * @return array<int, string>
     */
    private function labels(string $model, ?array $ids): array
    {
        return $ids === null || $ids === [] ? [] : $model::query()->whereKey($ids)->get()->mapWithKeys(static fn ($row): array => [(int) $row->getKey() => (string) $row->getAttribute('name')])->all();
    }
}
