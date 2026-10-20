<?php

namespace App\Repositories\Contracts\Provider;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\DocumentSubject;
use App\Models\OwnedDocument;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Documents of one subject kind (provider or vehicle). Both implementations share one shape, so the document
 * services work on either through this contract.
 */
interface DocumentRepositoryInterface
{
    public function subject(): DocumentSubject;

    /**
     * Live (not superseded) documents of an owner, newest first, document type loaded.
     *
     * @return Collection<int, OwnedDocument>
     */
    public function forOwner(int $ownerId): Collection;

    /**
     * @param list<int> $ownerIds
     * @return Collection<int, OwnedDocument>
     */
    public function forOwners(array $ownerIds): Collection;

    /**
     * @return Builder<OwnedDocument>
     */
    public function datatableQuery(int $ownerId): Builder;

    public function findByUuid(string $uuid): ?OwnedDocument;

    public function lockForUpdate(int $id): ?OwnedDocument;

    /**
     * Pending uploads of the same owner and type (a new upload replaces them).
     *
     * @return Collection<int, OwnedDocument>
     */
    public function pendingFor(int $ownerId, int $typeId): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): OwnedDocument;

    /**
     * Soft-deletes a replaced upload and drops its file reference.
     */
    public function supersede(OwnedDocument $document): void;

    public function applyReview(OwnedDocument $document, DocumentStatus $status, ?int $adminId, ?string $reason): OwnedDocument;

    /**
     * Approved documents whose expiry date is before $today.
     *
     * @return Collection<int, OwnedDocument>
     */
    public function expiredBefore(CarbonInterface $today, int $limit): Collection;

    /**
     * Approved documents that expire between $today and $until (inclusive) and have not yet been reminded at a
     * threshold this close.
     *
     * @return Collection<int, OwnedDocument>
     */
    public function expiringBetween(CarbonInterface $today, CarbonInterface $until, int $limit): Collection;

    public function recordReminder(OwnedDocument $document, int $thresholdDays): void;

    /**
     * Every live document of an owner (any status) with its file path, for deleting files on anonymisation.
     *
     * @return Collection<int, OwnedDocument>
     */
    public function withFilesForOwner(int $ownerId): Collection;

    /**
     * Removes the personal data of an owner's documents (file reference, number, original name) but keeps the review history.
     */
    public function scrubForOwner(int $ownerId): void;
}
