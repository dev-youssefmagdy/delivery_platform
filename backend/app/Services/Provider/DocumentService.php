<?php

namespace App\Services\Provider;

use App\DTOs\Provider\DocumentUploadData;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\DocumentSubject;
use App\Enums\Provider\FileScanStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Models\Admin;
use App\Models\DocumentType;
use App\Models\OwnedDocument;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\DocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Provider and vehicle documents: the upload pipeline (type rules, size, extension + sniffed MIME, image decode and
 * re-encode, malware scan, private storage, encrypted number) and the admin review (approve / reject, audited).
 * Files never leave the private disk except through signed routes (`DocumentAccessService`).
 */
final class DocumentService
{
    public function __construct(
        private readonly ProviderDocumentRepositoryInterface $providerDocuments,
        private readonly VehicleDocumentRepositoryInterface $vehicleDocuments,
        private readonly DocumentTypeRepositoryInterface $types,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly UploadInspector $inspector,
        private readonly UploadScanner $scanner,
        private readonly PrivateFileStorage $storage,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation
     * @throws ServiceUnavailableException `file_scanner_unavailable`
     */
    public function upload(ProviderProfile $profile, DocumentUploadData $data): OwnedDocument
    {
        $type = $this->types->findActiveByCode($data->documentTypeCode) ?? throw BusinessRuleViolation::make('document_type_unknown', 'document_type');
        $vehicle = $this->vehicleFor($profile, $type, $data->vehicleUuid);
        $this->assertDates($type, $data);

        $number = $data->number !== null ? trim($data->number) : null;

        if ($type->requires_number && ($number === null || $number === '')) {
            throw BusinessRuleViolation::make('document_number_required', 'number');
        }

        $inspected = $this->inspector->inspectDocument($data->file, $type->allowed_mimes, $type->max_size_kb);

        try {
            $scan = $this->scanner->scan((string) $data->file->getRealPath());
        } catch (FileScannerUnavailableException) {
            throw ServiceUnavailableException::make('file_scanner_unavailable', 'file');
        }

        if ($scan->isInfected()) {
            throw BusinessRuleViolation::make('document_infected', 'file');
        }

        $path = $this->storage->putDocument($profile->uuid, $inspected);
        $repository = $this->repository($type->applies_to);
        $replacedPaths = [];

        try {
            $document = $this->db->transaction(function () use ($profile, $type, $vehicle, $data, $inspected, $scan, $path, $number, $repository, &$replacedPaths): OwnedDocument {
                $locked = $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);

                if (! $locked->status->allowsDocumentsAndVehicles()) {
                    throw BusinessRuleViolation::make('application_locked');
                }

                $ownerId = $vehicle->id ?? $locked->id;

                foreach ($repository->pendingFor($ownerId, $type->id) as $replaced) {
                    $replacedPaths[] = $replaced->path;
                    $repository->supersede($replaced);
                }

                return $repository->create([
                    ($vehicle === null ? 'provider_profile_id' : 'vehicle_id') => $ownerId,
                    'document_type_id' => $type->id,
                    'path' => $path,
                    'original_name' => $inspected->originalName === '' ? null : $inspected->originalName,
                    'mime' => $inspected->mime,
                    'size_bytes' => $inspected->size(),
                    'file_hash' => $inspected->hash(),
                    'scan_status' => $scan->status,
                    'number' => $number === '' ? null : $number,
                    'issued_at' => $data->issuedAt?->toDateString(),
                    'expires_at' => $data->expiresAt?->toDateString(),
                    'status' => DocumentStatus::Pending,
                ]);
            });
        } catch (Throwable $exception) {
            $this->storage->delete($path);

            throw $exception;
        }

        $this->storage->deleteMany($replacedPaths);

        return $document->load('documentType');
    }

    /**
     * Every live document of the provider and of its vehicles, newest first.
     *
     * @return Collection<int, OwnedDocument>
     */
    public function listFor(ProviderProfile $profile): Collection
    {
        $vehicleIds = $this->vehicles->forProvider($profile)->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $all = $this->providerDocuments->forOwner($profile->id)->concat($vehicleIds === [] ? [] : $this->vehicleDocuments->forOwners($vehicleIds));

        return $all->sortByDesc('created_at')->values();
    }

    /**
     * A document by uuid from either table, whoever owns it (callers authorize with the policy).
     */
    public function findAny(string $uuid): ?OwnedDocument
    {
        return $this->providerDocuments->findByUuid($uuid) ?? $this->vehicleDocuments->findByUuid($uuid);
    }

    public function findOwned(ProviderProfile $profile, string $uuid): ?OwnedDocument
    {
        $document = $this->providerDocuments->findByUuid($uuid);

        if ($document !== null) {
            return $document->getAttribute('provider_profile_id') === $profile->id ? $document : null;
        }

        $document = $this->vehicleDocuments->findByUuid($uuid);

        if ($document === null) {
            return null;
        }

        $vehicle = $this->vehicles->find($document->getAttribute('vehicle_id'));

        return $vehicle !== null && $vehicle->provider_profile_id === $profile->id ? $document : null;
    }

    /**
     * Finds a document by uuid in either table, for admin routes (`{subject}` selects the table).
     */
    public function find(DocumentSubject $subject, string $uuid): ?OwnedDocument
    {
        return $this->repository($subject)->findByUuid($uuid);
    }

    /**
     * @throws BusinessRuleViolation `document_not_reviewable`, `document_already_expired`
     */
    public function approve(OwnedDocument $document, Admin $actor): OwnedDocument
    {
        return $this->db->transaction(function () use ($document, $actor): OwnedDocument {
            $locked = $this->lock($document);

            if ($locked->status !== DocumentStatus::Pending) {
                throw BusinessRuleViolation::make('document_not_reviewable');
            }

            if ($locked->expires_at !== null && $locked->expires_at->lessThan(CarbonImmutable::today())) {
                throw BusinessRuleViolation::make('document_already_expired');
            }

            if ($locked->scan_status === FileScanStatus::Infected) {
                throw BusinessRuleViolation::make('document_infected');
            }

            $before = ['status' => $locked->status->value];
            $this->repository($this->subjectOf($locked))->applyReview($locked, DocumentStatus::Approved, $actor->id, null);
            $this->audit->record(AuditAction::Approved, $locked->auditTarget(), $actor, $before, ['status' => DocumentStatus::Approved->value], null, $this->meta($locked));

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `document_not_reviewable`
     */
    public function reject(OwnedDocument $document, string $reason, ?Admin $actor): OwnedDocument
    {
        return $this->db->transaction(function () use ($document, $reason, $actor): OwnedDocument {
            $locked = $this->lock($document);

            if (! in_array($locked->status, [DocumentStatus::Pending, DocumentStatus::Approved], true)) {
                throw BusinessRuleViolation::make('document_not_reviewable');
            }

            $before = ['status' => $locked->status->value];
            $this->repository($this->subjectOf($locked))->applyReview($locked, DocumentStatus::Rejected, $actor?->id, $reason);
            $this->audit->record(AuditAction::Rejected, $locked->auditTarget(), $actor, $before, ['status' => DocumentStatus::Rejected->value], $reason, $this->meta($locked));

            return $locked;
        });
    }

    public function subjectOf(OwnedDocument $document): DocumentSubject
    {
        return $document instanceof \App\Models\VehicleDocument ? DocumentSubject::Vehicle : DocumentSubject::Provider;
    }

    public function repository(DocumentSubject $subject): DocumentRepositoryInterface
    {
        return $subject === DocumentSubject::Vehicle ? $this->vehicleDocuments : $this->providerDocuments;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(OwnedDocument $document): array
    {
        $document->loadMissing('documentType');

        return ['document' => $document->uuid, 'document_type' => $document->documentType->code, 'subject' => $this->subjectOf($document)->value];
    }

    private function vehicleFor(ProviderProfile $profile, DocumentType $type, ?string $vehicleUuid): ?Vehicle
    {
        if ($type->applies_to === DocumentSubject::Provider) {
            return null;
        }

        if ($vehicleUuid === null || $vehicleUuid === '') {
            throw BusinessRuleViolation::make('document_vehicle_required', 'vehicle_id');
        }

        $vehicle = $this->vehicles->findByUuidWithRelations($vehicleUuid);

        if ($vehicle === null || $vehicle->provider_profile_id !== $profile->id) {
            throw BusinessRuleViolation::make('document_vehicle_required', 'vehicle_id');
        }

        return $vehicle;
    }

    private function assertDates(DocumentType $type, DocumentUploadData $data): void
    {
        $today = CarbonImmutable::today();

        if ($type->requires_expiry && $data->expiresAt === null) {
            throw BusinessRuleViolation::make('document_expiry_required', 'expires_at');
        }

        if ($data->expiresAt !== null && $data->expiresAt->lessThan($today)) {
            throw BusinessRuleViolation::make('document_already_expired', 'expires_at');
        }

        if ($data->issuedAt !== null && $data->issuedAt->greaterThan($today)) {
            throw BusinessRuleViolation::make('document_issue_date_future', 'issued_at');
        }

        if ($data->issuedAt !== null && $data->expiresAt !== null && $data->expiresAt->lessThanOrEqualTo($data->issuedAt)) {
            throw BusinessRuleViolation::make('document_dates_inconsistent', 'expires_at');
        }
    }

    private function lock(OwnedDocument $document): OwnedDocument
    {
        return $this->repository($this->subjectOf($document))->lockForUpdate($document->id) ?? throw (new ModelNotFoundException)->setModel($document::class);
    }
}
