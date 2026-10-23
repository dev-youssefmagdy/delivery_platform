<?php

namespace App\Repositories\Eloquent\Safety;

use App\Models\SafetyIncident;
use App\Models\SafetyIncidentAttachment;
use App\Models\SafetyIncidentEvent;
use App\Repositories\Contracts\Safety\IncidentThreadRepositoryInterface;
use Illuminate\Support\Collection;

final class IncidentThreadRepository implements IncidentThreadRepositoryInterface
{
    public function recordEvent(SafetyIncident $incident, string $type, string $actorType, ?int $actorId, ?string $body = null, bool $public = false, ?array $meta = null): SafetyIncidentEvent
    {
        return SafetyIncidentEvent::query()->create([
            'incident_id' => $incident->id,
            'type' => $type,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'body' => $body,
            'is_public' => $public,
            'meta' => $meta,
        ]);
    }

    public function events(SafetyIncident $incident, bool $publicOnly = false): Collection
    {
        return SafetyIncidentEvent::query()
            ->where('incident_id', $incident->id)
            ->when($publicOnly, static fn ($query) => $query->where('is_public', true))
            ->orderBy('id')
            ->get()
            ->toBase();
    }

    public function createAttachment(array $attributes): SafetyIncidentAttachment
    {
        return SafetyIncidentAttachment::query()->create($attributes);
    }

    public function attachments(SafetyIncident $incident): Collection
    {
        return SafetyIncidentAttachment::query()->where('incident_id', $incident->id)->whereNull('purged_at')->orderBy('id')->get()->toBase();
    }

    public function countAttachments(SafetyIncident $incident): int
    {
        return SafetyIncidentAttachment::query()->where('incident_id', $incident->id)->whereNull('purged_at')->count();
    }

    public function findAttachment(SafetyIncident $incident, string $uuid): ?SafetyIncidentAttachment
    {
        return SafetyIncidentAttachment::query()->where('incident_id', $incident->id)->where('uuid', $uuid)->whereNull('purged_at')->first();
    }
}
