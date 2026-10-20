<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\ServiceFamily;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;

/**
 * ServiceFamilys for remote selects (requires the catalog permission).
 */
final class ServiceFamilyLookup implements Lookup
{
    public function __construct(private readonly ServiceFamilyRepositoryInterface $items) {}

    public function permission(): AdminPermission
    {
        return AdminPermission::ServicesManage;
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
     * @param array<int, ServiceFamily> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(
            static fn (ServiceFamily $item): LookupOptionData => new LookupOptionData($item->id, $item->name.' ('.$item->code.')', ['code' => $item->code]),
            $items,
        ));
    }
}
