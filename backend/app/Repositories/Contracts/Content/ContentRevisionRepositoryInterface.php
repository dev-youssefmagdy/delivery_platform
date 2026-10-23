<?php

namespace App\Repositories\Contracts\Content;

use App\Enums\Content\ContentType;
use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Collection;

interface ContentRevisionRepositoryInterface
{
    /**
     * Next revision number of an entity. Call inside the transaction that holds the entity row lock.
     */
    public function nextNumber(ContentType $type, int $id): int;

    /**
     * @param array<string, mixed> $attributes
     */
    public function append(array $attributes): ContentRevision;

    /**
     * Newest first, with the author.
     *
     * @return Collection<int, ContentRevision>
     */
    public function forEntity(ContentType $type, int $id, int $limit = 50): Collection;

    public function findForEntity(ContentType $type, int $id, int $number): ?ContentRevision;

    public function deleteForEntity(ContentType $type, int $id): void;
}
