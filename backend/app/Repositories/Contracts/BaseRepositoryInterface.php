<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface BaseRepositoryInterface
{
    /**
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * @return TModel|null
     */
    public function find(int|string $id): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(int|string $id): Model;

    /**
     * @return TModel|null
     */
    public function findByUuid(string $uuid): ?Model;

    /**
     * @param array<string, mixed> $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param TModel $model
     * @param array<string, mixed> $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * @param TModel $model
     */
    public function delete(Model $model): bool;

    /**
     * Pessimistic lock (SELECT … FOR UPDATE). Must be called inside a transaction.
     *
     * @return TModel|null
     */
    public function lockById(int|string $id): ?Model;

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 25): LengthAwarePaginator;
}
