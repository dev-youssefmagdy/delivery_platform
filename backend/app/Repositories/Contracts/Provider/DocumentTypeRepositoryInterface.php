<?php

namespace App\Repositories\Contracts\Provider;

use App\DTOs\Provider\DocumentTypeFilterData;
use App\Enums\Provider\DocumentSubject;
use App\Models\DocumentType;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<DocumentType>
 */
interface DocumentTypeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<DocumentType>
     */
    public function datatableQuery(DocumentTypeFilterData $filters): Builder;

    public function codeExists(string $code, ?int $exceptId = null): bool;

    public function findActiveByCode(string $code): ?DocumentType;

    /**
     * @return Collection<int, DocumentType>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, DocumentType>
     */
    public function findManyByIds(array $ids): Collection;

    /**
     * @return Collection<int, DocumentType>
     */
    public function active(?DocumentSubject $subject = null): Collection;

    public function requirementCount(DocumentType $type): int;

    public function documentCount(DocumentType $type): int;
}
