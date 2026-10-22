<?php

namespace App\Support\Files;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provider documents and vehicle photos on the private disk (ADR-11). Paths are random and never contain the original
 * file name; files are only ever served through short-lived signed routes after a policy check.
 */
final class PrivateFileStorage
{
    public function disk(): Filesystem
    {
        return Storage::disk((string) config('provider.documents.disk', 'private'));
    }

    public function putDocument(string $providerUuid, InspectedUpload $upload): string
    {
        $path = $this->base($providerUuid).'/documents/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    public function putVehiclePhoto(string $providerUuid, string $vehicleUuid, InspectedUpload $upload): string
    {
        $path = $this->base($providerUuid).'/vehicles/'.$vehicleUuid.'/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    /**
     * Proof of a payout transfer or of an adjustment (a bank receipt): one random file under the finance evidence folder.
     */
    public function putFinanceEvidence(string $kind, string $uuid, InspectedUpload $upload): string
    {
        $path = 'finance-evidence/'.$kind.'/'.$uuid.'/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    /**
     * One image of a booking chat message: `chat/{bookingUuid}/{random}.jpg`.
     */
    public function putChatAttachment(string $bookingUuid, InspectedUpload $upload): string
    {
        $path = 'chat/'.$bookingUuid.'/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    /**
     * Evidence on a support ticket: `support/{ticketUuid}/{random}.{ext}`.
     */
    public function putTicketAttachment(string $ticketUuid, InspectedUpload $upload): string
    {
        $path = 'support/'.$ticketUuid.'/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    /**
     * One picture of a delivery proof: `proofs/{bookingUuid}/{stage}/{random}.jpg`.
     */
    public function putProof(string $bookingUuid, string $stage, InspectedUpload $upload): string
    {
        $path = 'proofs/'.$bookingUuid.'/'.$stage.'/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    /**
     * A photo, document or video a customer attached to a moving / freight request: `booking-attachments/{customerUuid}/{random}.{ext}`.
     */
    public function putBookingAttachment(string $customerUuid, InspectedUpload $upload): string
    {
        $path = 'booking-attachments/'.$customerUuid.'/'.Str::uuid().'.'.$upload->extension;
        $this->disk()->put($path, $upload->bytes);

        return $path;
    }

    /**
     * Same folder, streamed from a local file (videos are too large to hold in memory).
     */
    public function putBookingAttachmentFile(string $customerUuid, string $localPath, string $extension): string
    {
        $path = 'booking-attachments/'.$customerUuid.'/'.Str::uuid().'.'.$extension;
        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new \RuntimeException('Attachment file is not readable.');
        }

        try {
            $this->disk()->put($path, $stream);
        } finally {
            fclose($stream);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            $this->disk()->delete($path);
        }
    }

    /**
     * @param list<string|null> $paths
     */
    public function deleteMany(array $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && $this->disk()->exists($path);
    }

    public function response(string $path, string $mime, bool $download, string $downloadName): StreamedResponse
    {
        $disposition = $download ? 'attachment' : 'inline';

        return $this->disk()->response($path, $downloadName, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    private function base(string $providerUuid): string
    {
        return trim((string) config('provider.documents.directory', 'providers'), '/').'/'.$providerUuid;
    }
}
