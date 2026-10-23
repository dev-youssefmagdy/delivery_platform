<?php

namespace App\Repositories\Contracts\Safety;

use App\Models\SafetyIncident;
use App\Models\SafetyIncidentAttachment;
use App\Models\SafetyIncidentEvent;
use Illuminate\Support\Collection;

/**
 * Timeline events and restricted attachments of a safety case.
 */
interface IncidentThreadRepositoryInterface
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function recordEvent(SafetyIncident $incident, string $type, string $actorType, ?int $actorId, ?string $body = null, bool $public = false, ?array $meta = null): SafetyIncidentEvent;

    /**
     * @return Collection<int, SafetyIncidentEvent> oldest first; `$publicOnly` is what the person may read
     */
    public function events(SafetyIncident $incident, bool $publicOnly = false): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createAttachment(array $attributes): SafetyIncidentAttachment;

    /**
     * @return Collection<int, SafetyIncidentAttachment> not purged, oldest first
     */
    public function attachments(SafetyIncident $incident): Collection;

    public function countAttachments(SafetyIncident $incident): int;

    public function findAttachment(SafetyIncident $incident, string $uuid): ?SafetyIncidentAttachment;
}
