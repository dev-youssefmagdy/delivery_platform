<?php

namespace App\Services\Support;

use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Repositories\Contracts\Support\TicketThreadRepositoryInterface;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Evidence on tickets (SRS §14.3): every file goes through the same pipeline as provider documents (extension against sniffed
 * type, decode and re-encode of images, refusal of PDFs with active content, size limit, malware scan) and lands on the
 * private disk; it is served through signed routes only. Inspecting and scanning happen BEFORE any row is written, so a bad
 * file never leaves a half-created message behind.
 */
final class TicketAttachmentService
{
    public function __construct(
        private readonly UploadInspector $inspector,
        private readonly UploadScanner $scanner,
        private readonly PrivateFileStorage $storage,
        private readonly TicketThreadRepositoryInterface $thread,
    ) {}

    /**
     * Validates and scans every file; nothing is stored yet.
     *
     * @param list<UploadedFile> $files
     * @return list<array{upload: \App\Support\Files\InspectedUpload, scan: \App\Enums\Provider\FileScanStatus}>
     *
     * @throws BusinessRuleViolation
     * @throws ServiceUnavailableException `file_scanner_unavailable`
     */
    public function inspectAll(array $files): array
    {
        if (count($files) > (int) config('support.tickets.attachments_per_message', 3)) {
            throw BusinessRuleViolation::make('ticket_too_many_attachments', 'attachments', ['max' => (int) config('support.tickets.attachments_per_message', 3)]);
        }

        $checked = [];

        foreach ($files as $file) {
            $inspected = $this->inspector->inspectDocument(
                $file,
                (array) config('support.tickets.attachment_mimes', []),
                (int) config('support.tickets.attachment_max_kilobytes', 8192),
            );

            try {
                $scan = $this->scanner->scan((string) $file->getRealPath());
            } catch (FileScannerUnavailableException) {
                throw ServiceUnavailableException::make('file_scanner_unavailable', 'attachments');
            }

            if ($scan->isInfected()) {
                throw BusinessRuleViolation::make('document_infected', 'attachments');
            }

            $checked[] = ['upload' => $inspected, 'scan' => $scan->status];
        }

        return $checked;
    }

    /**
     * Writes the checked files for a message. Call inside the message's transaction; returns the paths so the caller can remove
     * the files if the transaction fails.
     *
     * @param list<array{upload: \App\Support\Files\InspectedUpload, scan: \App\Enums\Provider\FileScanStatus}> $checked
     * @return list<string>
     */
    public function store(SupportTicket $ticket, int $messageId, array $checked): array
    {
        $paths = [];

        foreach ($checked as $item) {
            $path = $this->storage->putTicketAttachment($ticket->uuid, $item['upload']);
            $paths[] = $path;
            $this->thread->createAttachment([
                'ticket_id' => $ticket->id,
                'ticket_message_id' => $messageId,
                'path' => $path,
                'original_name' => $item['upload']->originalName === '' ? null : $item['upload']->originalName,
                'mime' => $item['upload']->mime,
                'size_bytes' => $item['upload']->size(),
                'file_hash' => $item['upload']->hash(),
                'scan_status' => $item['scan'],
            ]);
        }

        return $paths;
    }

    /**
     * @param list<string> $paths
     */
    public function discard(array $paths): void
    {
        $this->storage->deleteMany($paths);
    }

    public function serve(TicketAttachment $attachment, bool $download = false): StreamedResponse
    {
        $name = ($attachment->original_name ?: $attachment->uuid).'.'.($attachment->mime === 'application/pdf' ? 'pdf' : 'jpg');

        return $this->storage->response($attachment->path, $attachment->mime, $download, $name);
    }

    public function exists(TicketAttachment $attachment): bool
    {
        return $this->storage->exists($attachment->path);
    }
}
