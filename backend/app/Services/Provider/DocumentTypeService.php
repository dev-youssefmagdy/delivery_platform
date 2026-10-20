<?php

namespace App\Services\Provider;

use App\DTOs\Provider\DocumentRequirementData;
use App\DTOs\Provider\DocumentTypeData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Repositories\Contracts\Provider\DocumentRequirementRepositoryInterface;
use App\Repositories\Contracts\Provider\DocumentTypeRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Admin configuration of document types and of the requirements that make them mandatory per region / service /
 * vehicle category / provider type (SRS §7.1, §16.3). Audited writes, optimistic locking, immutable code; a type that
 * already has uploads can be deactivated but not deleted. Changing a requirement changes who is eligible, so it
 * carries a reason.
 */
final class DocumentTypeService
{
    public function __construct(
        private readonly DocumentTypeRepositoryInterface $types,
        private readonly DocumentRequirementRepositoryInterface $requirements,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(DocumentTypeData $data, ?Admin $actor): DocumentType
    {
        $this->assertMimes($data);

        if ($this->types->codeExists($data->code)) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }

        try {
            return $this->db->transaction(function () use ($data, $actor): DocumentType {
                /** @var DocumentType $type */
                $type = $this->types->create($data->attributes() + ['active' => true]);
                $this->audit->record(AuditAction::Created, $type, $actor, after: $type->auditSnapshot());

                return $type;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }
    }

    public function update(DocumentType $type, DocumentTypeData $data, ?Admin $actor): DocumentType
    {
        $this->assertMimes($data);

        return $this->db->transaction(function () use ($type, $data, $actor): DocumentType {
            $locked = $this->lock($type);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($data->code !== $locked->code) {
                throw BusinessRuleViolation::make('catalog_code_immutable', 'code');
            }

            if ($data->appliesTo !== $locked->applies_to && $this->types->documentCount($locked) > 0) {
                throw BusinessRuleViolation::make('document_type_subject_locked', 'applies_to');
            }

            $before = $locked->auditSnapshot();
            $this->types->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function setActive(DocumentType $type, bool $active, ?string $reason, ?Admin $actor): DocumentType
    {
        return $this->db->transaction(function () use ($type, $active, $reason, $actor): DocumentType {
            $locked = $this->lock($type);

            if ($locked->active !== $active) {
                $this->types->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
                $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);
            }

            return $locked;
        });
    }

    public function delete(DocumentType $type, string $reason, ?Admin $actor): void
    {
        $this->db->transaction(function () use ($type, $reason, $actor): void {
            $locked = $this->lock($type);

            if ($this->types->documentCount($locked) > 0) {
                throw BusinessRuleViolation::make('document_type_in_use');
            }

            $before = $locked->auditSnapshot();
            $this->types->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    public function addRequirement(DocumentType $type, DocumentRequirementData $data, string $reason, ?Admin $actor): DocumentRequirement
    {
        return $this->db->transaction(function () use ($type, $data, $reason, $actor): DocumentRequirement {
            $locked = $this->lock($type);

            /** @var DocumentRequirement $requirement */
            $requirement = $this->requirements->create($data->attributes() + ['document_type_id' => $locked->id]);
            $this->audit->record(AuditAction::Created, $requirement, $actor, after: $requirement->auditSnapshot(), reason: $reason, meta: ['document_type' => $locked->code]);

            return $requirement;
        });
    }

    public function removeRequirement(DocumentType $type, int $requirementId, string $reason, ?Admin $actor): void
    {
        $this->db->transaction(function () use ($type, $requirementId, $reason, $actor): void {
            $locked = $this->lock($type);
            $requirement = $this->requirements->find($requirementId);

            if ($requirement === null || $requirement->document_type_id !== $locked->id) {
                throw (new ModelNotFoundException)->setModel(DocumentRequirement::class);
            }

            $before = $requirement->auditSnapshot();
            $this->requirements->delete($requirement);
            $this->audit->record(AuditAction::Deleted, $requirement, $actor, before: $before, reason: $reason, meta: ['document_type' => $locked->code]);
        });
    }

    private function assertMimes(DocumentTypeData $data): void
    {
        $supported = array_keys((array) config('provider.documents.mime_extensions', []));

        if ($data->allowedMimes === [] || array_diff($data->allowedMimes, $supported) !== []) {
            throw BusinessRuleViolation::make('document_type_mimes_invalid', 'allowed_mimes');
        }
    }

    private function lock(DocumentType $type): DocumentType
    {
        $locked = $this->types->lockById($type->id);

        return $locked instanceof DocumentType ? $locked : throw (new ModelNotFoundException)->setModel(DocumentType::class);
    }
}
