<?php

namespace App\Repositories\Eloquent\Provider;

use App\Enums\Provider\DocumentStatus;
use App\Models\OwnedDocument;
use App\Repositories\Contracts\Provider\DocumentRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Queries shared by provider and vehicle documents; subclasses name the model and the owner column.
 */
abstract class AbstractDocumentRepository implements DocumentRepositoryInterface
{
    /**
     * @return class-string<OwnedDocument>
     */
    abstract protected function modelClass(): string;

    abstract protected function ownerColumn(): string;

    /**
     * Relations that lead from a document to its provider profile (eager loaded by the job queries).
     *
     * @return list<string>
     */
    abstract protected function ownerRelations(): array;

    /**
     * @return Builder<OwnedDocument>
     */
    protected function query(): Builder
    {
        return $this->modelClass()::query();
    }

    public function forOwner(int $ownerId): Collection
    {
        return $this->query()->with(['documentType', ...$this->ownerRelations()])->where($this->ownerColumn(), $ownerId)->orderByDesc('id')->get();
    }

    public function forOwners(array $ownerIds): Collection
    {
        return $this->query()->with(['documentType', ...$this->ownerRelations()])->whereIn($this->ownerColumn(), $ownerIds)->orderByDesc('id')->get();
    }

    public function datatableQuery(int $ownerId): Builder
    {
        return $this->query()->with(['documentType', 'reviewer:id,name', ...$this->ownerRelations()])->where($this->ownerColumn(), $ownerId);
    }

    public function findByUuid(string $uuid): ?OwnedDocument
    {
        return $this->query()->with(['documentType', ...$this->ownerRelations()])->where('uuid', $uuid)->first();
    }

    public function lockForUpdate(int $id): ?OwnedDocument
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function pendingFor(int $ownerId, int $typeId): Collection
    {
        return $this->query()
            ->where($this->ownerColumn(), $ownerId)
            ->where('document_type_id', $typeId)
            ->where('status', DocumentStatus::Pending->value)
            ->lockForUpdate()
            ->get();
    }

    public function create(array $attributes): OwnedDocument
    {
        return $this->query()->create($attributes);
    }

    public function supersede(OwnedDocument $document): void
    {
        $document->forceFill(['path' => null])->save();
        $document->delete();
    }

    public function applyReview(OwnedDocument $document, DocumentStatus $status, ?int $adminId, ?string $reason): OwnedDocument
    {
        $document->forceFill([
            'status' => $status,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
            'rejection_reason' => $status === DocumentStatus::Rejected ? $reason : null,
        ])->save();

        return $document;
    }

    public function expiredBefore(CarbonInterface $today, int $limit): Collection
    {
        return $this->query()
            ->with(['documentType', ...$this->ownerRelations()])
            ->where('status', DocumentStatus::Approved->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $today->toDateString())
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();
    }

    public function expiringBetween(CarbonInterface $today, CarbonInterface $until, int $limit): Collection
    {
        return $this->query()
            ->with(['documentType', ...$this->ownerRelations()])
            ->where('status', DocumentStatus::Approved->value)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$today->toDateString(), $until->toDateString()])
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();
    }

    public function recordReminder(OwnedDocument $document, int $thresholdDays): void
    {
        $document->forceFill(['expiry_reminder_days' => $thresholdDays])->save();
    }

    public function withFilesForOwner(int $ownerId): Collection
    {
        return $this->query()->withTrashed()->where($this->ownerColumn(), $ownerId)->get();
    }

    public function scrubForOwner(int $ownerId): void
    {
        $this->query()->withTrashed()->where($this->ownerColumn(), $ownerId)->update([
            'path' => null,
            'number' => null,
            'original_name' => null,
        ]);
    }
}
