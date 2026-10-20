<?php

namespace App\Repositories\Contracts\Catalog;

use App\DTOs\Catalog\ServiceFamilyFilterData;
use App\Models\ServiceFamily;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ServiceFamily>
 */
interface ServiceFamilyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<ServiceFamily>
     */
    public function datatableQuery(ServiceFamilyFilterData $filters): Builder;

    /**
     * @return Collection<int, ServiceFamily>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, ServiceFamily>
     */
    public function findManyByIds(array $ids): Collection;

    /**
     * Every family, ordered, for selects and filters.
     *
     * @return Collection<int, ServiceFamily>
     */
    public function allOrdered(): Collection;

    public function codeExists(string $code, ?int $exceptId = null): bool;

    public function categoryCount(ServiceFamily $family): int;

    public function activeCategoryCount(ServiceFamily $family): int;

    /**
     * Families shown to clients: active ones that own at least one active, published category.
     *
     * @return Collection<int, ServiceFamily>
     */
    public function publicList(): Collection;
}
