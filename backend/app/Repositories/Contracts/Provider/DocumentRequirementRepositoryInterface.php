<?php

namespace App\Repositories\Contracts\Provider;

use App\Enums\Provider\DocumentSubject;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRequirementRepositoryInterface
{
    /**
     * @return Builder<DocumentRequirement>
     */
    public function datatableQuery(DocumentType $type): Builder;

    public function find(int $id): ?DocumentRequirement;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): DocumentRequirement;

    public function delete(DocumentRequirement $requirement): void;

    /**
     * Every requirement whose document type is active and about the given subject (type loaded).
     *
     * @return Collection<int, DocumentRequirement>
     */
    public function activeFor(DocumentSubject $subject): Collection;
}
