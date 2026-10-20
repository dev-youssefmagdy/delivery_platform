<?php

namespace App\Repositories\Contracts\Geography;

use App\Enums\Geo\SavedPlaceType;
use App\Models\SavedPlace;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Saved places are always read through the owner's id: there is no lookup by uuid alone (IDOR-proof).
 *
 * @extends BaseRepositoryInterface<SavedPlace>
 */
interface SavedPlaceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, SavedPlace>
     */
    public function forUser(int $userId): Collection;

    public function findForUser(int $userId, string $uuid): ?SavedPlace;

    public function countForUser(int $userId): int;

    public function typeTaken(int $userId, SavedPlaceType $type, ?int $exceptId = null): bool;
}
