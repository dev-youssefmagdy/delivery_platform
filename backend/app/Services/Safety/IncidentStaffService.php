<?php

namespace App\Services\Safety;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Safety\IncidentEscalationLevel;
use App\Enums\Safety\IncidentEventType;
use App\Enums\Safety\IncidentOutcome;
use App\Enums\Safety\IncidentPriority;
use App\Enums\Safety\IncidentSeverity;
use App\Enums\Safety\IncidentStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\SafetyIncident;
use App\Models\SafetyIncidentAttachment;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Contracts\Safety\IncidentThreadRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Files\PrivateFileStorage;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Work on a safety case from the Safety Center (SRS §16.10): acknowledge, assign, notes, messages to the person, triage,
 * escalation, resolution and legal hold. Every change runs in a transaction on a locked row, writes a timeline event and an audit
 * entry (reason where dangerous). Evidence is handled separately: every view or download of narrative, location, notes and
 * files writes an audit entry with the acting admin.
 */
final class IncidentStaffService
{
    public function __construct(
        private readonly SafetyIncidentRepositoryInterface $incidents,
        private readonly IncidentThreadRepositoryInterface $thread,
        private readonly TrustedContactRepositoryInterface $contacts,
        private readonly AdminRepositoryInterface $admins,
        private readonly SafetyNotifier $notifier,
        private readonly PrivateFileStorage $storage,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return list<Admin> officers a case can be assigned to
     */
    public function assignees(): array
    {
        try {
            return array_values($this->admins->activeWithPermission(AdminPermission::IncidentsManage->value)->all());
        } catch (PermissionDoesNotExist) {
            return [];
        }
    }

    /**
     * Overview data that needs only `incidents.view`: no narrative, no location, no note bodies, no files.
     *
     * @return Collection<int, \App\Models\SafetyIncidentEvent>
     */
    public function timeline(SafetyIncident $incident): Collection
    {
        return $this->thread->events($incident);
    }

    public function acknowledge(SafetyIncident $incident, Admin $actor): SafetyIncident
    {
        $acknowledged = $this->mutate($incident, function (SafetyIncident $locked) use ($actor): void {
            if ($locked->status !== IncidentStatus::New) {
                throw BusinessRuleViolation::make('incident_not_new');
            }

            $before = ['status' => $locked->status->value, 'assigned_admin_id' => $locked->assigned_admin_id];
            $attributes = ['status' => IncidentStatus::Acknowledged, 'acknowledged_at' => now()];

            if ($locked->assigned_admin_id === null) {
                $attributes += ['assigned_admin_id' => $actor->id, 'assigned_at' => now()];
            }

            $this->incidents->update($locked, $attributes);
            $this->thread->recordEvent($locked, IncidentEventType::Acknowledged->value, ActorType::Admin->value, $actor->id, null, true);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: $before, after: ['status' => IncidentStatus::Acknowledged->value, 'assigned_admin_id' => $locked->assigned_admin_id]);
        });
        $this->notifier->userUpdated($acknowledged, 'ack');

        return $acknowledged;
    }

    public function assign(SafetyIncident $incident, Admin $actor, ?int $adminId, ?string $reason = null): SafetyIncident
    {
        return $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $adminId, $reason): void {
            $this->assertActive($locked);

            if ($adminId !== null && ! in_array($adminId, array_map(static fn (Admin $admin): int => $admin->id, $this->assignees()), true)) {
                throw BusinessRuleViolation::make('incident_assignee_invalid', 'admin_id');
            }

            $before = ['assigned_admin_id' => $locked->assigned_admin_id];
            $this->incidents->update($locked, ['assigned_admin_id' => $adminId, 'assigned_at' => $adminId === null ? null : now()]);
            $this->thread->recordEvent($locked, ($adminId === null ? IncidentEventType::Unassigned : IncidentEventType::Assigned)->value, ActorType::Admin->value, $actor->id, null, false, ['from' => $before['assigned_admin_id'], 'to' => $adminId]);
            $this->audit->record(AuditAction::Assigned, $locked, $actor, before: $before, after: ['assigned_admin_id' => $adminId], reason: $reason);
        });
    }

    public function addNote(SafetyIncident $incident, Admin $actor, string $body): void
    {
        $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $body): void {
            $this->thread->recordEvent($locked, IncidentEventType::Note->value, ActorType::Admin->value, $actor->id, trim($body));
            $this->audit->record(AuditAction::MessageSent, $locked, $actor, meta: ['visibility' => 'internal']);
        });
    }

    /**
     * A message the person reads in the app; it also counts as the acknowledgement of a new case.
     */
    public function messageUser(SafetyIncident $incident, Admin $actor, string $body): SafetyIncident
    {
        $sent = $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $body): void {
            $this->assertActive($locked);
            $this->thread->recordEvent($locked, IncidentEventType::PublicMessage->value, ActorType::Admin->value, $actor->id, trim($body), true);

            if ($locked->status === IncidentStatus::New) {
                $this->incidents->update($locked, ['status' => IncidentStatus::Acknowledged, 'acknowledged_at' => now(), 'assigned_admin_id' => $locked->assigned_admin_id ?? $actor->id, 'assigned_at' => $locked->assigned_admin_id === null ? now() : $locked->assigned_at]);
            }

            $this->audit->record(AuditAction::MessageSent, $locked, $actor, meta: ['visibility' => 'public']);
        });
        $this->notifier->userUpdated($sent, 'msg-'.now()->timestamp);

        return $sent;
    }

    public function triage(SafetyIncident $incident, Admin $actor, IncidentSeverity $severity, IncidentPriority $priority, string $reason): SafetyIncident
    {
        return $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $severity, $priority, $reason): void {
            $this->assertActive($locked);
            $before = ['severity' => $locked->severity->value, 'priority' => $locked->priority->value, 'sla_due_at' => $locked->sla_due_at?->toIso8601String()];
            $attributes = ['severity' => $severity, 'priority' => $priority];

            if ($locked->status === IncidentStatus::New && $priority !== $locked->priority) {
                $attributes['sla_due_at'] = CarbonImmutable::instance($locked->created_at)->addMinutes($priority->slaMinutes());
            }

            $this->incidents->update($locked, $attributes);

            if ($before['severity'] !== $severity->value) {
                $this->thread->recordEvent($locked, IncidentEventType::SeverityChanged->value, ActorType::Admin->value, $actor->id, null, false, ['from' => $before['severity'], 'to' => $severity->value]);
            }

            if ($before['priority'] !== $priority->value) {
                $this->thread->recordEvent($locked, IncidentEventType::PriorityChanged->value, ActorType::Admin->value, $actor->id, null, false, ['from' => $before['priority'], 'to' => $priority->value]);
            }

            $this->audit->record(AuditAction::Updated, $locked, $actor, before: $before, after: ['severity' => $severity->value, 'priority' => $priority->value, 'sla_due_at' => $locked->sla_due_at?->toIso8601String()], reason: $reason);
        });
    }

    public function escalate(SafetyIncident $incident, Admin $actor, IncidentEscalationLevel $level, string $reason): SafetyIncident
    {
        return $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $level, $reason): void {
            $this->assertActive($locked);
            $before = ['status' => $locked->status->value, 'escalation_level' => $locked->escalation_level?->value];
            $this->incidents->update($locked, [
                'status' => IncidentStatus::Escalated,
                'escalation_level' => $level,
                'escalated_at' => now(),
                'acknowledged_at' => $locked->acknowledged_at ?? now(),
            ]);
            $this->thread->recordEvent($locked, IncidentEventType::Escalated->value, ActorType::Admin->value, $actor->id, $reason, false, ['from' => $before['escalation_level'], 'to' => $level->value]);
            $this->audit->record(AuditAction::Escalated, $locked, $actor, before: $before, after: ['status' => IncidentStatus::Escalated->value, 'escalation_level' => $level->value], reason: $reason);
        });
    }

    /**
     * @throws BusinessRuleViolation `incident_finished`
     */
    public function resolve(SafetyIncident $incident, Admin $actor, IncidentOutcome $outcome, string $reason, ?string $publicMessage = null): SafetyIncident
    {
        $resolved = $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $outcome, $reason, $publicMessage): void {
            $this->assertActive($locked);
            $before = ['status' => $locked->status->value];
            $this->incidents->update($locked, [
                'status' => IncidentStatus::Resolved,
                'outcome' => $outcome,
                'resolution_reason' => $reason,
                'resolved_at' => now(),
                'acknowledged_at' => $locked->acknowledged_at ?? now(),
                'assigned_admin_id' => $locked->assigned_admin_id ?? $actor->id,
                'assigned_at' => $locked->assigned_admin_id === null ? now() : $locked->assigned_at,
            ]);
            $this->thread->recordEvent($locked, IncidentEventType::Resolved->value, ActorType::Admin->value, $actor->id, $publicMessage === null || trim($publicMessage) === '' ? null : trim($publicMessage), true, ['outcome' => $outcome->value]);
            $this->audit->record(AuditAction::Resolved, $locked, $actor, before: $before, after: ['status' => IncidentStatus::Resolved->value, 'outcome' => $outcome->value], reason: $reason);
        });
        $this->notifier->userUpdated($resolved, 'resolved');

        return $resolved;
    }

    public function reopen(SafetyIncident $incident, Admin $actor, string $reason): SafetyIncident
    {
        return $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $reason): void {
            if ($locked->status !== IncidentStatus::Resolved) {
                throw BusinessRuleViolation::make('incident_not_resolved');
            }

            $this->incidents->update($locked, ['status' => IncidentStatus::Investigating, 'resolved_at' => null, 'outcome' => null, 'resolution_reason' => null]);
            $this->thread->recordEvent($locked, IncidentEventType::Reopened->value, ActorType::Admin->value, $actor->id, $reason);
            $this->audit->record(AuditAction::Reopened, $locked, $actor, before: ['status' => IncidentStatus::Resolved->value], after: ['status' => IncidentStatus::Investigating->value], reason: $reason);
        });
    }

    public function setLegalHold(SafetyIncident $incident, Admin $actor, bool $hold, string $reason): SafetyIncident
    {
        return $this->mutate($incident, function (SafetyIncident $locked) use ($actor, $hold, $reason): void {
            if ($locked->legal_hold === $hold) {
                throw BusinessRuleViolation::make($hold ? 'incident_hold_already_set' : 'incident_hold_not_set');
            }

            $this->incidents->update($locked, [
                'legal_hold' => $hold,
                'legal_hold_at' => $hold ? now() : null,
                'legal_hold_by_admin_id' => $hold ? $actor->id : null,
                'legal_hold_reason' => $hold ? $reason : null,
            ]);
            $this->thread->recordEvent($locked, ($hold ? IncidentEventType::LegalHoldSet : IncidentEventType::LegalHoldReleased)->value, ActorType::Admin->value, $actor->id, $reason);
            $this->audit->record(AuditAction::Restricted, $locked, $actor, before: ['legal_hold' => ! $hold], after: ['legal_hold' => $hold], reason: $reason);
        });
    }

    /**
     * The evidence of the case for an admin holding `incidents.view_evidence` (the caller checks the policy). Writing the audit
     * entry is part of reading: no entry, no evidence.
     *
     * @return array<string, mixed>
     */
    public function evidence(SafetyIncident $incident, Admin $actor): array
    {
        $this->audit->record(AuditAction::Viewed, $incident, $actor, meta: ['evidence' => ['narrative', 'callback_phone', 'location', 'notes', 'attachments_list', 'contact_actions']]);

        return [
            'narrative' => $incident->narrative,
            'callback_phone' => $incident->callbackPhone(),
            'location' => $incident->location_snapshot,
            'events' => $this->thread->events($incident),
            'attachments' => $this->thread->attachments($incident)->map(fn (SafetyIncidentAttachment $attachment): array => [
                'uuid' => $attachment->uuid,
                'mime' => $attachment->mime,
                'size_bytes' => $attachment->size_bytes,
                'hash' => $attachment->file_hash,
                'scan' => $attachment->scan_status->label(),
                'at' => $attachment->created_at,
                'uploader' => $attachment->uploader_type,
                'url' => URL::temporarySignedRoute('admin.safety-incidents.attachments.show', now()->addMinutes((int) config('safety.evidence_url_ttl_minutes', 5)), ['incident' => $incident->uuid, 'attachment' => $attachment->uuid]),
            ])->all(),
            'contact_actions' => $this->contacts->actionsForIncident($incident->id),
        ];
    }

    public function streamAttachment(SafetyIncident $incident, string $attachmentUuid, Admin $actor): StreamedResponse
    {
        $attachment = $this->thread->findAttachment($incident, $attachmentUuid);

        if (! $attachment instanceof SafetyIncidentAttachment || ! $this->storage->exists($attachment->path)) {
            throw (new ModelNotFoundException)->setModel(SafetyIncidentAttachment::class);
        }

        $this->audit->record(AuditAction::Viewed, $incident, $actor, meta: ['file' => 'attachment', 'attachment' => $attachment->uuid, 'mime' => $attachment->mime]);

        return $this->storage->response((string) $attachment->path, $attachment->mime, false, $attachment->uuid.($attachment->mime === 'application/pdf' ? '.pdf' : '.jpg'));
    }

    /**
     * Restricted evidence export of one case (`incidents.export_restricted`): timeline with bodies, location, contact actions and
     * an attachment manifest with hashes (the files themselves go through the audited stream). Audited and recorded on the timeline.
     *
     * @return array<string, mixed>
     */
    public function evidenceBundle(SafetyIncident $incident, Admin $actor): array
    {
        $this->audit->record(AuditAction::Exported, $incident, $actor, meta: ['export' => 'evidence_bundle']);
        $this->thread->recordEvent($incident, IncidentEventType::Exported->value, ActorType::Admin->value, $actor->id);

        return [
            'exported_at' => now()->toIso8601String(),
            'exported_by_admin' => $actor->id,
            'incident' => [
                'number' => $incident->number, 'uuid' => $incident->uuid, 'trigger' => $incident->trigger->value, 'category' => $incident->category->value,
                'severity' => $incident->severity->value, 'priority' => $incident->priority->value, 'status' => $incident->status->value,
                'occurred_at' => $incident->occurred_at->toIso8601String(), 'acknowledged_at' => $incident->acknowledged_at?->toIso8601String(),
                'resolved_at' => $incident->resolved_at?->toIso8601String(), 'outcome' => $incident->outcome?->value, 'resolution_reason' => $incident->resolution_reason,
                'legal_hold' => $incident->legal_hold, 'booking_code' => $incident->context['booking_code'] ?? null,
                'narrative' => $incident->narrative, 'callback_phone' => $incident->callbackPhone(), 'location' => $incident->location_snapshot,
            ],
            'timeline' => $this->thread->events($incident)->map(static fn ($event): array => [
                'type' => $event->type->value, 'actor' => $event->actor_type->value, 'actor_id' => $event->actor_id, 'at' => $event->created_at->toIso8601String(),
                'public' => $event->is_public, 'body' => $event->body, 'meta' => $event->meta,
            ])->all(),
            'trusted_contact_actions' => $this->contacts->actionsForIncident($incident->id)->map(static fn ($action): array => [
                'action' => $action->action->value, 'status' => $action->status->value, 'reason' => $action->reason, 'at' => $action->created_at->toIso8601String(),
                'location_included' => $action->included_location, 'trip_link_included' => $action->included_trip_link,
            ])->all(),
            'attachments' => $this->thread->attachments($incident)->map(static fn (SafetyIncidentAttachment $a): array => [
                'uuid' => $a->uuid, 'mime' => $a->mime, 'size_bytes' => $a->size_bytes, 'sha256' => $a->file_hash, 'scan' => $a->scan_status->value, 'uploaded_at' => $a->created_at->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Scheduler: flags new cases whose acknowledgement is overdue.
     */
    public function flagSlaBreaches(int $batch = 200): int
    {
        $count = 0;

        foreach ($this->incidents->slaOverdue(CarbonImmutable::now(), $batch) as $incident) {
            $this->db->transaction(function () use ($incident, &$count): void {
                $locked = $this->incidents->lockById($incident->id);

                if (! $locked instanceof SafetyIncident || $locked->sla_breached_at !== null || $locked->status !== IncidentStatus::New) {
                    return;
                }

                $this->incidents->update($locked, ['sla_breached_at' => now()]);
                $this->thread->recordEvent($locked, IncidentEventType::SlaBreached->value, ActorType::System->value, null, null, false, ['due_at' => $locked->sla_due_at?->toIso8601String()]);
                $count++;
            });
            $this->notifier->feedChanged($incident->refresh());
        }

        return $count;
    }

    /**
     * Scheduler: removes the evidence of cases resolved longer ago than the retention period, unless a legal hold is set.
     */
    public function purgeExpired(int $batch = 100): int
    {
        $files = [];
        $count = 0;
        $cutoff = CarbonImmutable::now()->subDays((int) config('safety.retention_days', 730));

        foreach ($this->incidents->expired($cutoff, $batch) as $incident) {
            $this->db->transaction(function () use ($incident, &$files, &$count): void {
                foreach ($this->thread->attachments($incident) as $attachment) {
                    $files[] = (string) $attachment->path;
                    $attachment->forceFill(['path' => null, 'purged_at' => now()])->save();
                }

                $this->incidents->update($incident, ['narrative' => null, 'callback_phone_encrypted' => null, 'location_snapshot' => null]);
                $this->audit->record(AuditAction::Anonymised, $incident, null, meta: ['reason' => 'retention']);
                $count++;
            });
        }

        $this->storage->deleteMany($files);

        return $count;
    }

    private function mutate(SafetyIncident $incident, callable $change): SafetyIncident
    {
        return $this->db->transaction(function () use ($incident, $change): SafetyIncident {
            $locked = $this->incidents->lockById($incident->id);

            if (! $locked instanceof SafetyIncident) {
                throw (new ModelNotFoundException)->setModel(SafetyIncident::class);
            }

            $change($locked);
            $this->notifier->feedChanged($locked);

            return $locked;
        });
    }

    private function assertActive(SafetyIncident $incident): void
    {
        if (! $incident->status->isActive()) {
            throw BusinessRuleViolation::make('incident_finished');
        }
    }
}
