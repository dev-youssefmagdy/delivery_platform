<?php

namespace App\Services\Scope;

use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use finfo;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Photos, videos and documents of a moving / freight request (SRS §5.1-D, §5.1-E) through the private file pipeline:
 * sniffed MIME against the client extension, size caps, images decoded and re-encoded (EXIF / GPS dropped), PDFs refused when
 * active, everything scanned through the FileScanner adapter (infected = refused, unavailable = 503), random names on the private disk.
 *
 * A file is uploaded first (unattached, capped per customer) and then referenced by uuid from the service form; linking it to a booking
 * enforces ownership and the per-booking limits. Only the booking's customer and its SELECTED provider can read a file, via signed routes.
 * Videos are scanned and size-capped but not re-encoded (no transcoder in the stack), so they are never shown before selection.
 */
final class BookingAttachmentService
{
    /**
     * Kinds each form field may reference.
     *
     * @var array<string, list<string>>
     */
    private const FIELD_KINDS = [
        'documents' => [BookingAttachment::KIND_DOCUMENT, BookingAttachment::KIND_PHOTO],
    ];

    public function __construct(
        private readonly BookingAttachmentRepositoryInterface $attachments,
        private readonly BookingRepositoryInterface $bookings,
        private readonly UploadInspector $inspector,
        private readonly UploadScanner $scanner,
        private readonly PrivateFileStorage $storage,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation|ConflictException|ServiceUnavailableException
     */
    public function upload(User $customer, UploadedFile $file, string $kind): BookingAttachment
    {
        $cfg = (array) config('offer_scope.attachments');

        if ($this->attachments->countUnattached($customer->id) >= (int) $cfg['max_unattached']) {
            throw ConflictException::make('attachment_pending_limit', 'file', ['max' => (int) $cfg['max_unattached']]);
        }

        [$mime, $extension, $size, $hash, $bytes] = $this->inspect($file, $kind, $cfg);

        try {
            $scan = $this->scanner->scan((string) $file->getRealPath());
        } catch (FileScannerUnavailableException) {
            throw ServiceUnavailableException::make('file_scanner_unavailable', 'file');
        }

        if ($scan->isInfected()) {
            throw BusinessRuleViolation::make('document_infected', 'file');
        }

        $path = $bytes === null
            ? $this->storage->putBookingAttachmentFile($customer->uuid, (string) $file->getRealPath(), $extension)
            : $this->storage->putBookingAttachment($customer->uuid, $bytes);

        try {
            return $this->attachments->insert([
                'customer_id' => $customer->id,
                'kind' => $kind,
                'disk' => (string) config('provider.documents.disk', 'private'),
                'storage_path' => $path,
                'mime' => $mime,
                'size_bytes' => $size,
                'sha256' => $hash,
                'scan_status' => $scan->status->value,
            ]);
        } catch (Throwable $exception) {
            $this->storage->delete($path);

            throw $exception;
        }
    }

    /**
     * Only an upload that no booking references yet can be removed; a referenced file leaves with an edit of the request.
     *
     * @throws ConflictException `attachment_in_use`
     */
    public function discard(User $customer, string $uuid): void
    {
        $attachment = $this->attachments->findOfCustomer($customer->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(BookingAttachment::class);

        if ($attachment->booking_id !== null) {
            throw ConflictException::make('attachment_in_use');
        }

        $path = $attachment->storage_path;
        $this->attachments->delete($attachment);
        $this->storage->delete($path);
    }

    /**
     * Links the uploads referenced by the request to the booking and drops the ones it no longer references. Runs inside the
     * booking's transaction; files of dropped rows are removed after it commits.
     *
     * @param array<string, list<string>> $refs field key => attachment uuids
     *
     * @throws BusinessRuleViolation `attachment_invalid`, `attachment_limit_exceeded`
     */
    public function sync(Booking $booking, int $customerId, array $refs): void
    {
        $uuids = array_values(array_unique(array_merge([], ...array_values($refs))));
        $owned = $uuids === [] ? new Collection : $this->attachments->lockOwned($customerId, $uuids)->keyBy('uuid');
        $cfg = (array) config('offer_scope.attachments');
        $limits = [BookingAttachment::KIND_PHOTO => (int) $cfg['max_photos'], BookingAttachment::KIND_VIDEO => (int) $cfg['max_videos'], BookingAttachment::KIND_DOCUMENT => (int) $cfg['max_documents']];
        $counts = [BookingAttachment::KIND_PHOTO => 0, BookingAttachment::KIND_VIDEO => 0, BookingAttachment::KIND_DOCUMENT => 0];
        $linked = [];

        foreach ($refs as $field => $list) {
            foreach ($list as $index => $uuid) {
                $attachment = $owned->get($uuid);
                $allowed = self::FIELD_KINDS[$field] ?? [BookingAttachment::KIND_PHOTO, BookingAttachment::KIND_VIDEO];

                if (! $attachment instanceof BookingAttachment
                    || ($attachment->booking_id !== null && $attachment->booking_id !== $booking->id)
                    || ! in_array($attachment->kind, $allowed, true)) {
                    throw BusinessRuleViolation::make('attachment_invalid', "service_details.{$field}.{$index}");
                }

                if (++$counts[$attachment->kind] > $limits[$attachment->kind]) {
                    throw BusinessRuleViolation::make('attachment_limit_exceeded', "service_details.{$field}", ['max' => $limits[$attachment->kind], 'kind' => $attachment->kind]);
                }

                $linked[$attachment->id] = [$attachment, $field];
            }
        }

        $paths = [];

        foreach ($this->attachments->forBooking($booking->id) as $existing) {
            if (! isset($linked[$existing->id])) {
                $paths[] = $existing->storage_path;
                $this->attachments->delete($existing);
            }
        }

        foreach ($linked as [$attachment, $field]) {
            if ($attachment->booking_id !== $booking->id || $attachment->field !== $field) {
                $this->attachments->update($attachment, ['booking_id' => $booking->id, 'field' => $field]);
            }
        }

        if ($paths !== []) {
            $this->db->afterCommit(fn () => $this->storage->deleteMany($paths));
        }
    }

    /**
     * @return Collection<int, BookingAttachment>
     */
    public function listForCustomer(User $customer, string $bookingUuid): Collection
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->attachments->forBooking($booking->id);
    }

    /**
     * @return Collection<int, BookingAttachment>
     */
    public function listForProvider(ProviderProfile $provider, string $bookingUuid): Collection
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->attachments->forBooking($booking->id);
    }

    public function bookingOfCustomer(User $customer, string $bookingUuid): Booking
    {
        return $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
    }

    public function bookingOfProvider(ProviderProfile $provider, string $bookingUuid): Booking
    {
        return $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
    }

    public function stream(Booking $booking, string $attachmentUuid): StreamedResponse
    {
        $attachment = $this->attachments->findInBooking($booking->id, $attachmentUuid);

        if (! $attachment instanceof BookingAttachment || ! $this->storage->exists($attachment->storage_path)) {
            throw (new ModelNotFoundException)->setModel(BookingAttachment::class);
        }

        $extension = pathinfo((string) $attachment->storage_path, PATHINFO_EXTENSION);

        return $this->storage->response((string) $attachment->storage_path, $attachment->mime, false, $attachment->uuid.'.'.$extension);
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{0: string, 1: string, 2: int, 3: string, 4: \App\Support\Files\InspectedUpload|null} mime, extension, size, sha256, bytes (null = stream the file)
     */
    private function inspect(UploadedFile $file, string $kind, array $cfg): array
    {
        if ($kind === BookingAttachment::KIND_VIDEO) {
            return $this->inspectVideo($file, $cfg);
        }

        $inspected = $kind === BookingAttachment::KIND_PHOTO
            ? $this->inspector->inspectPhoto($file, (int) $cfg['photo_max_kilobytes'], (int) $cfg['photo_max_edge'])
            : $this->inspector->inspectDocument($file, ['application/pdf', 'image/jpeg', 'image/png'], (int) $cfg['document_max_kilobytes']);

        return [$inspected->mime, $inspected->extension, $inspected->size(), $inspected->hash(), $inspected];
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array{0: string, 1: string, 2: int, 3: string, 4: null}
     */
    private function inspectVideo(UploadedFile $file, array $cfg): array
    {
        if (! $file->isValid() || $file->getRealPath() === false) {
            throw BusinessRuleViolation::make('document_upload_failed', 'file');
        }

        $size = $file->getSize();

        if ($size === false || $size < 1) {
            throw BusinessRuleViolation::make('document_unreadable', 'file');
        }

        if ($size > (int) $cfg['video_max_kilobytes'] * 1024) {
            throw BusinessRuleViolation::make('document_too_large', 'file', ['kilobytes' => (int) $cfg['video_max_kilobytes']]);
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file((string) $file->getRealPath());
        $map = (array) $cfg['video_mimes'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! isset($map[$mime]) || ! in_array($extension, (array) $map[$mime], true)) {
            throw BusinessRuleViolation::make('document_type_not_allowed', 'file');
        }

        return [$mime, $extension, (int) $size, (string) hash_file('sha256', (string) $file->getRealPath()), null];
    }
}
