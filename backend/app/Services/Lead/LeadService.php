<?php

namespace App\Services\Lead;

use App\DTOs\Lead\LeadSubmissionData;
use App\DTOs\Lead\LeadUpdateData;
use App\Enums\Common\AuditAction;
use App\Enums\Lead\LeadStatus;
use App\Enums\Lead\LeadType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\Lead;
use App\Repositories\Contracts\Lead\LeadRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Onboarding leads (SRS 17.3, 16.13): created by the public provider form, worked by staff.
 *
 * Submission is idempotent per phone and UTC day (a double click or a retry returns the same reference and sends no second
 * notification). Staff moves a lead through LeadStatus (reason for reject / spam / reopen), every change is audited and
 * guarded by the row lock and `lock_version`.
 */
final class LeadService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leads,
        private readonly LeadNotifier $notifier,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array{lead: Lead, created: bool}
     */
    public function submit(LeadSubmissionData $data): array
    {
        $key = hash('sha256', LeadType::ProviderOnboarding->value.'|'.$data->phoneE164.'|'.now()->utc()->toDateString());

        if (($existing = $this->leads->findByDuplicateKey($key)) instanceof Lead) {
            return ['lead' => $existing, 'created' => false];
        }

        try {
            $lead = $this->db->transaction(fn (): Lead => $this->leads->createNumbered([
                'type' => LeadType::ProviderOnboarding,
                'status' => LeadStatus::New,
                'name' => $data->name,
                'phone_e164' => $data->phoneE164,
                'email' => $data->email,
                'city_id' => $data->cityId,
                'city_other' => $data->cityId === null ? $data->cityOther : null,
                'service_category_id' => $data->serviceCategoryId,
                'vehicle_note' => $data->vehicleNote,
                'message' => $data->message,
                'locale' => $data->locale,
                'consented_at' => now(),
                'ip_hash' => $data->ip === null ? null : hash_hmac('sha256', $data->ip, (string) config('app.key')),
                'duplicate_key' => $key,
            ]));
        } catch (UniqueConstraintViolationException) {
            // Two identical submissions raced: the other one won.
            return ['lead' => $this->leads->findByDuplicateKey($key) ?? throw new ModelNotFoundException, 'created' => false];
        }

        $this->notifier->received($lead);

        return ['lead' => $lead, 'created' => true];
    }

    /**
     * @throws BusinessRuleViolation `lead_transition_invalid`, `lead_reason_required`
     */
    public function transition(Lead $lead, Admin $actor, LeadStatus $target, ?string $reason): Lead
    {
        return $this->db->transaction(function () use ($lead, $actor, $target, $reason): Lead {
            $locked = $this->leads->lockById($lead->id) ?? throw (new ModelNotFoundException)->setModel(Lead::class);

            if (! $locked->status->canMoveTo($target)) {
                throw BusinessRuleViolation::make('lead_transition_invalid');
            }

            if ($target->needsReason() && ($reason === null || trim($reason) === '')) {
                throw BusinessRuleViolation::make('lead_reason_required', 'reason');
            }

            $before = ['status' => $locked->status->value];
            $this->leads->update($locked, [
                'status' => $target,
                'status_reason' => $reason,
                'status_changed_at' => now(),
                'assigned_admin_id' => $locked->assigned_admin_id ?? $actor->id,
                'lock_version' => $locked->lock_version + 1,
            ]);

            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: $before, after: ['status' => $target->value], reason: $reason, meta: ['reference' => $locked->reference]);

            return $locked;
        });
    }

    /**
     * @throws ConflictException `stale_version`
     */
    public function update(Lead $lead, Admin $actor, LeadUpdateData $data): Lead
    {
        return $this->db->transaction(function () use ($lead, $actor, $data): Lead {
            $locked = $this->leads->lockById($lead->id) ?? throw (new ModelNotFoundException)->setModel(Lead::class);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = ['assigned_admin_id' => $locked->assigned_admin_id, 'notes_set' => $locked->notes !== null];
            $this->leads->update($locked, ['notes' => $data->notes, 'assigned_admin_id' => $data->assignedAdminId, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::Updated, $locked, $actor, before: $before, after: ['assigned_admin_id' => $data->assignedAdminId, 'notes_set' => $data->notes !== null], meta: ['reference' => $locked->reference]);

            return $locked;
        });
    }
}
