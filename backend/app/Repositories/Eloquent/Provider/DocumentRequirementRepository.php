<?php

namespace App\Repositories\Eloquent\Provider;

use App\Enums\Provider\DocumentSubject;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Repositories\Contracts\Provider\DocumentRequirementRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class DocumentRequirementRepository implements DocumentRequirementRepositoryInterface
{
    public function datatableQuery(DocumentType $type): Builder
    {
        return DocumentRequirement::query()
            ->with(['country', 'city', 'serviceCategory', 'vehicleCategory'])
            ->where('document_type_id', $type->id);
    }

    public function find(int $id): ?DocumentRequirement
    {
        return DocumentRequirement::query()->find($id);
    }

    public function create(array $attributes): DocumentRequirement
    {
        return DocumentRequirement::query()->create($attributes);
    }

    public function delete(DocumentRequirement $requirement): void
    {
        $requirement->delete();
    }

    public function activeFor(DocumentSubject $subject): Collection
    {
        return DocumentRequirement::query()
            ->with('documentType')
            ->whereHas('documentType', static fn (Builder $query) => $query->where('active', true)->where('applies_to', $subject->value))
            ->get();
    }
}
