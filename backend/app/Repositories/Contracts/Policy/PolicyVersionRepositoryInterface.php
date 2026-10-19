<?php

namespace App\Repositories\Contracts\Policy;

use App\DTOs\Policy\PolicyVersionFilterData;
use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<PolicyVersion>
 */
interface PolicyVersionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Listing query (DataTable) with the filters card applied.
     *
     * @return Builder<PolicyVersion>
     */
    public function datatableQuery(PolicyVersionFilterData $filters): Builder;

    /**
     * Locks every row of a type (SELECT … FOR UPDATE) so publishing is serialised per type. Inside a transaction.
     *
     * @return Collection<int, PolicyVersion>
     */
    public function lockByType(PolicyType $type): Collection;

    /**
     * Latest published version of each type (the version users must accept now).
     *
     * @return Collection<int, PolicyVersion>
     */
    public function currentPublished(): Collection;

    public function loadPublisher(PolicyVersion $policy): PolicyVersion;
}
