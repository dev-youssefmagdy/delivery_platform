<?php

namespace App\Services\Safety;

use App\Enums\Common\ActorType;
use App\Enums\Identity\ClientApp;
use App\Enums\Safety\IncidentEventType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Models\SafetyIncident;
use App\Models\SafetyIncidentAttachment;
use App\Models\User;
use App\Repositories\Contracts\Safety\IncidentThreadRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * What the person can do with their own case after raising it: read it (public timeline only, never staff notes), add a
 * written update and add evidence. Files take the same inspection and malware scan as every upload, are stored on the private
 * disk and are readable by the uploader through a signed link and by staff through the audited admin stream only.
 */
final class IncidentFollowUpService
{
    public function __construct(
        private readonly SafetyIncidentRepositoryInterface $incidents,
        private readonly IncidentThreadRepositoryInterface $thread,
        private readonly TrustedContactRepositoryInterface $contacts,
        private readonly UploadInspector $inspector,
        private readonly UploadScanner $scanner,
        private readonly PrivateFileStorage $storage,
        private readonly SafetyNotifier $notifier,
    ) {}

    public function find(User $user, ClientApp $app, string $uuid): SafetyIncident
    {
        $incident = $this->incidents->findOfReporter($uuid, $user->id);

        return $incident instanceof SafetyIncident && $incident->reporter_type === $app ? $incident : throw (new ModelNotFoundException)->setModel(SafetyIncident::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SafetyIncident>
     */
    public function queryFor(User $user, ClientApp $app): \Illuminate\Database\Eloquent\Builder
    {
        return $this->incidents->userQuery($user->id)->where('reporter_type', $app->value);
    }

    /**
     * @return Collection<int, \App\Models\SafetyIncidentEvent>
     */
    public function publicTimeline(SafetyIncident $incident): Collection
    {
        return $this->thread->events($incident, true);
    }

    /**
     * @return Collection<int, \App\Models\TrustedContactAction>
     */
    public function contactActions(SafetyIncident $incident): Collection
    {
        return $this->contacts->actionsForIncident($incident->id);
    }

    /**
     * @return Collection<int, SafetyIncidentAttachment>
     */
    public function attachments(SafetyIncident $incident): Collection
    {
        return $this->thread->attachments($incident)->filter(static fn (SafetyIncidentAttachment $a): bool => $a->uploader_type !== ActorType::Admin->value)->values();
    }

    public function addUpdate(User $user, ClientApp $app, string $uuid, string $body): SafetyIncident
    {
        $incident = $this->find($user, $app, $uuid);
        $body = trim($body);

        if ($body === '') {
            throw BusinessRuleViolation::make('safety_update_empty', 'body');
        }

        $this->thread->recordEvent($incident, IncidentEventType::UserUpdate->value, $app->value, $user->id, $body, true);
        $this->notifier->feedChanged($incident);

        return $incident;
    }

    /**
     * @throws BusinessRuleViolation `safety_too_many_attachments`
     * @throws ServiceUnavailableException `file_scanner_unavailable`
     */
    public function addAttachment(User $user, ClientApp $app, string $uuid, UploadedFile $file): SafetyIncidentAttachment
    {
        $incident = $this->find($user, $app, $uuid);

        if ($this->thread->countAttachments($incident) >= (int) config('safety.attachments.per_incident', 10)) {
            throw BusinessRuleViolation::make('safety_too_many_attachments', 'file', ['max' => (int) config('safety.attachments.per_incident', 10)]);
        }

        $inspected = $this->inspector->inspectDocument($file, (array) config('safety.attachments.mimes', []), (int) config('safety.attachments.max_kilobytes', 8192));

        try {
            $scan = $this->scanner->scan((string) $file->getRealPath());
        } catch (FileScannerUnavailableException) {
            throw ServiceUnavailableException::make('file_scanner_unavailable', 'file');
        }

        if ($scan->isInfected()) {
            throw BusinessRuleViolation::make('document_infected', 'file');
        }

        $path = $this->storage->putSafetyEvidence($incident->uuid, $inspected);

        try {
            $attachment = $this->thread->createAttachment([
                'incident_id' => $incident->id,
                'uploader_type' => $app->value,
                'uploader_id' => $user->id,
                'path' => $path,
                'mime' => $inspected->mime,
                'size_bytes' => $inspected->size(),
                'file_hash' => $inspected->hash(),
                'scan_status' => $scan->status,
            ]);
            $this->thread->recordEvent($incident, IncidentEventType::AttachmentAdded->value, $app->value, $user->id, null, false, ['attachment' => $attachment->uuid, 'mime' => $inspected->mime]);
        } catch (Throwable $e) {
            $this->storage->delete($path);

            throw $e;
        }

        $this->notifier->feedChanged($incident);

        return $attachment;
    }

    public function streamOwn(User $user, ClientApp $app, string $uuid, string $attachmentUuid): StreamedResponse
    {
        $incident = $this->find($user, $app, $uuid);
        $attachment = $this->thread->findAttachment($incident, $attachmentUuid);

        if (! $attachment instanceof SafetyIncidentAttachment || $attachment->uploader_id !== $user->id || ! $this->storage->exists($attachment->path)) {
            throw (new ModelNotFoundException)->setModel(SafetyIncidentAttachment::class);
        }

        return $this->storage->response((string) $attachment->path, $attachment->mime, false, $attachment->uuid.($attachment->mime === 'application/pdf' ? '.pdf' : '.jpg'));
    }
}
