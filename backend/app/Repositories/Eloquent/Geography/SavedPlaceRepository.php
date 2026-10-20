<?php

namespace App\Repositories\Eloquent\Geography;

use App\Enums\Geo\SavedPlaceType;
use App\Models\SavedPlace;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<SavedPlace>
 */
final class SavedPlaceRepository extends BaseRepository implements SavedPlaceRepositoryInterface
{
    public function __construct(SavedPlace $model)
    {
        parent::__construct($model);
    }

    public function forUser(int $userId): Collection
    {
        return $this->query()->where('user_id', $userId)->orderByRaw("field(type, 'home', 'work', 'other')")->orderBy('id')->get();
    }

    public function findForUser(int $userId, string $uuid): ?SavedPlace
    {
        return $this->query()->where('user_id', $userId)->where('uuid', $uuid)->first();
    }

    public function countForUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->count();
    }

    public function typeTaken(int $userId, SavedPlaceType $type, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('type', $type->value)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
