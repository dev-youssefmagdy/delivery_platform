<?php

namespace App\Repositories\Eloquent\Provider;

use App\DTOs\Provider\DocumentTypeFilterData;
use App\Enums\Provider\DocumentSubject;
use App\Models\DocumentType;
use App\Repositories\Contracts\Provider\DocumentTypeRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<DocumentType>
 */
final class DocumentTypeRepository extends BaseRepository implements DocumentTypeRepositoryInterface
{
    public function __construct(DocumentType $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(DocumentTypeFilterData $filters): Builder
    {
        return $this->query()
            ->withCount('requirements')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->appliesTo !== [], static fn (Builder $query) => $query->whereIn('applies_to', array_map(static fn (DocumentSubject $subject): string => $subject->value, $filters->appliesTo)))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('code', $code)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function findActiveByCode(string $code): ?DocumentType
    {
        return $this->query()->where('code', $code)->where('active', true)->first();
    }

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->when($term !== '', static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('code', 'like', $like)
                ->orWhere('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like)))
            ->orderBy('sort_order')->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->orderBy('sort_order')->get();
    }

    public function active(?DocumentSubject $subject = null): Collection
    {
        return $this->query()
            ->where('active', true)
            ->when($subject !== null, static fn (Builder $query) => $query->where('applies_to', $subject?->value))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    public function requirementCount(DocumentType $type): int
    {
        return $type->requirements()->count();
    }

    public function documentCount(DocumentType $type): int
    {
        return (int) DB::table('provider_documents')->where('document_type_id', $type->id)->count()
            + (int) DB::table('vehicle_documents')->where('document_type_id', $type->id)->count();
    }
}
