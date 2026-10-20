<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\DocumentStatus;
use App\Models\DocumentType;
use App\Models\OwnedDocument;
use App\Models\Vehicle;
use Carbon\CarbonInterface;

/**
 * One line of a document checklist: a required (or optional) document type and where the owner stands with it.
 * `state` is null when nothing was uploaded; an approved document past its expiry date counts as `expired` even
 * before the daily job marks it.
 */
final readonly class ChecklistItem
{
    public function __construct(
        public DocumentType $type,
        public bool $mandatory,
        public ?DocumentStatus $state,
        public ?OwnedDocument $document,
        public ?Vehicle $vehicle = null,
    ) {}

    public function isSatisfied(): bool
    {
        return $this->state === DocumentStatus::Approved;
    }

    public function isMissing(): bool
    {
        return $this->state === null;
    }

    /**
     * Uploaded and waiting for, or already past, review: enough to submit the application.
     */
    public function isUploaded(): bool
    {
        return $this->state === DocumentStatus::Pending || $this->state === DocumentStatus::Approved;
    }

    public function expiresAt(): ?CarbonInterface
    {
        return $this->document?->expires_at;
    }
}
