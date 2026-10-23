<?php

namespace App\Repositories\Eloquent\Content;

use App\Enums\Content\ContentType;
use App\Models\ContentRevision;
use App\Repositories\Contracts\Content\ContentRevisionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class ContentRevisionRepository implements ContentRevisionRepositoryInterface
{
    public function nextNumber(ContentType $type, int $id): int
    {
        return ((int) ContentRevision::query()
            ->where('revisionable_type', $type->value)
            ->where('revisionable_id', $id)
            ->max('number')) + 1;
    }

    public function append(array $attributes): ContentRevision
    {
        return ContentRevision::query()->create($attributes + ['created_at' => now()]);
    }

    public function forEntity(ContentType $type, int $id, int $limit = 50): Collection
    {
        return ContentRevision::query()
            ->with('admin:id,name')
            ->where('revisionable_type', $type->value)
            ->where('revisionable_id', $id)
            ->orderByDesc('number')
            ->limit($limit)
            ->get();
    }

    public function findForEntity(ContentType $type, int $id, int $number): ?ContentRevision
    {
        return ContentRevision::query()
            ->where('revisionable_type', $type->value)
            ->where('revisionable_id', $id)
            ->where('number', $number)
            ->first();
    }

    public function deleteForEntity(ContentType $type, int $id): void
    {
        ContentRevision::query()->where('revisionable_type', $type->value)->where('revisionable_id', $id)->delete();
    }
}
