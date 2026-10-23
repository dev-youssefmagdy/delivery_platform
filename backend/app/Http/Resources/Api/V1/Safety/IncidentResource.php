<?php

namespace App\Http\Resources\Api\V1\Safety;

use App\Models\SafetyIncident;
use App\Models\SafetyIncidentAttachment;
use App\Models\SafetyIncidentEvent;
use App\Models\TrustedContactAction;
use App\Services\Safety\EmergencyNumbers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * The person's own case. Staff notes, internal events, severity and priority never appear; the public timeline, the outcome, what
 * was done towards trusted contacts and the person's own files do.
 *
 * @property SafetyIncident $resource
 */
class IncidentResource extends JsonResource
{
    /**
     * @param Collection<int, SafetyIncidentEvent>|null $timeline
     * @param Collection<int, TrustedContactAction>|null $contactActions
     * @param Collection<int, SafetyIncidentAttachment>|null $attachments
     * @param list<array{code: string, status: string, message: string}>|null $actions
     */
    public function __construct(SafetyIncident $resource, private readonly ?Collection $timeline = null, private readonly ?Collection $contactActions = null, private readonly ?Collection $attachments = null, private readonly ?array $actions = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $incident = $this->resource;
        $detailed = $this->timeline !== null;

        $data = [
            'id' => $incident->uuid,
            'number' => $incident->number,
            'trigger' => $incident->trigger->value,
            'category' => $incident->category->value,
            'category_label' => $incident->category->label(),
            'status' => $incident->status->value,
            'status_label' => $incident->status->label(),
            'booking' => $incident->booking === null ? null : ['id' => $incident->booking->uuid, 'code' => $incident->booking->code],
            'occurred_at' => $incident->occurred_at->toIso8601ZuluString(),
            'acknowledged_at' => $incident->acknowledged_at?->toIso8601ZuluString(),
            'resolved_at' => $incident->resolved_at?->toIso8601ZuluString(),
            'outcome' => $incident->outcome?->value,
            'outcome_label' => $incident->outcome?->label(),
            'can_add_update' => $incident->status->isActive(),
        ];

        if (! $detailed) {
            return $data;
        }

        return $data + [
            'narrative' => $incident->narrative,
            'timeline' => $this->timeline->map(static fn (SafetyIncidentEvent $event): array => [
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'actor' => $event->actor_type->value === 'admin' ? 'staff' : $event->actor_type->value,
                'message' => $event->body,
                'at' => $event->created_at->toIso8601ZuluString(),
            ])->values()->all(),
            'trusted_contacts' => $this->contactActions?->map(static fn (TrustedContactAction $action): array => [
                'name' => $action->contact_name,
                'action' => $action->action->value,
                'status' => $action->status->value,
                'status_label' => $action->status->label(),
                'at' => $action->created_at->toIso8601ZuluString(),
            ])->values()->all(),
            'attachments' => $this->attachments?->map(static fn (SafetyIncidentAttachment $attachment): array => [
                'id' => $attachment->uuid,
                'mime' => $attachment->mime,
                'size_bytes' => $attachment->size_bytes,
                'created_at' => $attachment->created_at->toIso8601ZuluString(),
                'url' => URL::temporarySignedRoute('api.v1.safety.attachments.file', now()->addMinutes(10), ['incident' => $incident->uuid, 'attachment' => $attachment->uuid]),
            ])->values()->all(),
            'actions' => $this->actions,
            'emergency_numbers' => app(EmergencyNumbers::class)->forMarket(),
            'emergency_notice' => (string) __('safety.actions.emergency_not_contacted'),
        ];
    }
}
