<?php

namespace App\Services\Provider;

use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentSubject;
use App\Models\Admin;
use App\Models\OwnedDocument;
use App\Models\VehiclePhoto;
use App\Services\Audit\AuditLogger;
use App\Support\Files\PrivateFileStorage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * How private files reach a screen (ADR-11): never by a stored URL, always through a signed route valid for
 * `provider.documents.url_ttl_minutes` (5). Admin links are bound to the admin that asked for them and every
 * admin view or download of a document is written to the audit log when the file is actually served.
 */
final class DocumentAccessService
{
    public const MODE_VIEW = 'view';

    public const MODE_DOWNLOAD = 'download';

    public function __construct(
        private readonly DocumentService $documents,
        private readonly PrivateFileStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Fresh signed URL of a document for one admin (nothing is audited until the file is served).
     */
    public function adminUrl(OwnedDocument $document, Admin $admin, string $mode = self::MODE_VIEW): string
    {
        $route = $this->documents->subjectOf($document) === DocumentSubject::Vehicle ? 'admin.provider-files.vehicle-document' : 'admin.provider-files.provider-document';

        return URL::temporarySignedRoute(
            $route,
            now()->addMinutes($this->ttl()),
            ['document' => $document->uuid, 'admin' => $admin->id, 'mode' => $mode === self::MODE_DOWNLOAD ? self::MODE_DOWNLOAD : self::MODE_VIEW],
        );
    }

    public function adminPhotoUrl(VehiclePhoto $photo, Admin $admin): string
    {
        return URL::temporarySignedRoute('admin.provider-files.vehicle-photo', now()->addMinutes($this->ttl()), ['photo' => $photo->uuid, 'admin' => $admin->id]);
    }

    /**
     * Streams the document to an admin and audits the access (view or download). The file must exist: a missing
     * file answers 404 and is not audited as an access.
     */
    public function serveToAdmin(OwnedDocument $document, Admin $admin, string $mode): StreamedResponse
    {
        abort_unless($this->storage->exists($document->path), 404);

        $download = $mode === self::MODE_DOWNLOAD;
        $this->audit->record(
            $download ? AuditAction::Downloaded : AuditAction::Viewed,
            $document->auditTarget(),
            $admin,
            meta: $this->documents->meta($document) + ['scan_status' => $document->scan_status->value],
        );

        return $this->storage->response((string) $document->path, $document->mime, $download, $this->downloadName($document));
    }

    public function serveVehiclePhoto(VehiclePhoto $photo): StreamedResponse
    {
        abort_unless($this->storage->exists($photo->path), 404);

        return $this->storage->response($photo->path, $photo->mime, false, 'vehicle-photo.jpg');
    }

    /**
     * Signed URL for the document's owner (the driver app).
     */
    public function ownerUrl(OwnedDocument $document): string
    {
        return URL::temporarySignedRoute(
            'api.v1.provider.documents.file',
            now()->addMinutes($this->ttl()),
            ['document' => $document->uuid],
        );
    }

    public function ownerPhotoUrl(VehiclePhoto $photo): string
    {
        return URL::temporarySignedRoute('api.v1.provider.vehicle-photos.file', now()->addMinutes($this->ttl()), ['photo' => $photo->uuid]);
    }

    public function serveToOwner(OwnedDocument $document): StreamedResponse
    {
        abort_unless($this->storage->exists($document->path), 404);

        return $this->storage->response((string) $document->path, $document->mime, false, $this->downloadName($document));
    }

    public function subjectOf(OwnedDocument $document): DocumentSubject
    {
        return $this->documents->subjectOf($document);
    }

    private function downloadName(OwnedDocument $document): string
    {
        $extension = $document->mime === 'application/pdf' ? 'pdf' : 'jpg';

        return $document->documentType->code.'-'.substr($document->uuid, 0, 8).'.'.$extension;
    }

    private function ttl(): int
    {
        return (int) config('provider.documents.url_ttl_minutes', 5);
    }
}
