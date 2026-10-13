<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * All Eloquent access goes through repositories (Plan §4.2). Module repositories extend this class and add
 * intent-revealing query methods (filters, datatable queries, locking reads).
 *
 * @template TModel of Model
 *
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @param TModel $model
     */
    public function __construct(protected readonly Model $model) {}

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function findByUuid(string $uuid): ?Model
    {
        return $this->query()->where('uuid', $uuid)->first();
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes)->save();

        return $model;
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function lockById(int|string $id): ?Model
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->query()->latest($this->model->getKeyName())->paginate($perPage);
    }
}
