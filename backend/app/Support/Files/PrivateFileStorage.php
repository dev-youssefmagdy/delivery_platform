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
