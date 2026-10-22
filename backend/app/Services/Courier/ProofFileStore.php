<?php

namespace App\Services\Courier;

use App\Enums\Catalog\ProofStage;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Models\Booking;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use Illuminate\Http\UploadedFile;

/**
 * Turns an uploaded proof picture into a stored one: sniffed and decoded (so a renamed file is refused), re-encoded as a
 * bounded JPEG without metadata, scanned through the FileScanner adapter (an infected file is refused, an unavailable
 * scanner is a 503, never a silent pass), hashed (SHA-256 of the stored bytes) and written to the private disk under a
 * random name. Runs BEFORE the database transaction (the work is slow); the caller deletes the file when its write fails.
 */
final class ProofFileStore
{
    public function __construct(
        private readonly UploadInspector $inspector,
        private readonly UploadScanner $scanner,
        private readonly PrivateFileStorage $storage,
    ) {}

    /**
     * @throws BusinessRuleViolation file errors on field `file`, `document_infected`
     * @throws ServiceUnavailableException `file_scanner_unavailable`
     */
    public function store(UploadedFile $file, Booking $booking, ProofStage $stage): StoredProofFile
    {
        $inspected = $this->inspector->inspectPhoto(
            $file,
            (int) config('courier.proofs.max_kilobytes', 5120),
            (int) config('courier.proofs.max_edge', 1600),
        );

        try {
            $scan = $this->scanner->scan((string) $file->getRealPath());
        } catch (FileScannerUnavailableException) {
            throw ServiceUnavailableException::make('file_scanner_unavailable', 'file');
        }

        if ($scan->isInfected()) {
            throw BusinessRuleViolation::make('document_infected', 'file');
        }

        return new StoredProofFile(
            (string) config('provider.documents.disk', 'private'),
            $this->storage->putProof($booking->uuid, $stage->value, $inspected),
            $inspected->hash(),
            $inspected->mime,
            $inspected->size(),
            $scan->status,
        );
    }

    public function discard(StoredProofFile $file): void
    {
        $this->storage->delete($file->path);
    }
}
