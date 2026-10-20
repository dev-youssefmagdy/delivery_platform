<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;

/**
 * ServiceCategorys for remote selects (names only, public catalog data: any signed-in admin may use it, e.g. the provider filters).
 */
final class ServiceCategoryLookup implements Lookup
{
    public function __construct(private readonly ServiceCategoryRepositoryInterface $items) {}

    public function permission(): ?AdminPermission
    {
        return null;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->items->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->items->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, ServiceCategory> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(
            static fn (ServiceCategory $item): LookupOptionData => new LookupOptionData($item->id, $item->name.' ('.$item->code.')', ['code' => $item->code]),
            $items,
        ));
    }
}
