<?php

namespace App\Services\Support;

use App\DTOs\Support\StaffReplyData;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Support\TicketEventType;
use App\Enums\Support\TicketGroup;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Enums\Support\TicketVisibility;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Repositories\Contracts\Support\TicketThreadRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Ticket handling from the admin panel (SRS §14.3, §16.11): reply and internal notes, assignment, escalation to finance /
 * safety / operations, resolution, reopening, priority and the LINKS to finance operations. Every state change runs in one
 * transaction on a locked row, writes a ticket event and an audit entry (before/after, reason where the action is dangerous),
 * and notifies the person only for public replies and status changes they should know about.
 *
 * Nothing here moves money. A refund or wallet adjustment is a separate flow with its own permission and the ledger service;
 * the ticket only stores which payment or payout it is about.
 */
final class TicketStaffService
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly TicketThreadRepositoryInterface $thread,
        private readonly TicketAttachmentService $attachments,
        private readonly TicketAssigner $assigner,
        private readonly TicketNotifier $notifier,
        private readonly SlaCalculator $sla,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return Collection<int, TicketMessage> the full thread, internal notes included (staff screens only)
     */
    public function fullThread(SupportTicket $ticket): Collection
    {
        return $this->thread->messages($ticket, true);
    }

    /**
     * @return Collection<int, \App\Models\TicketEvent>
     */
    public function events(SupportTicket $ticket): Collection
    {
        return $this->thread->events($ticket);
    }

    /**
     * @return list<Admin> admins the ticket can be assigned to
     */
    public function assignees(SupportTicket $ticket): array
    {
        return $this->assigner->eligible($ticket->group);
    }

    public function attachmentFor(SupportTicket $ticket, string $attachmentUuid): StreamedResponse
    {
        $attachment = $this->thread->findAttachment($ticket, $attachmentUuid, true);

        if (! $attachment instanceof TicketAttachment || ! $this->attachments->exists($attachment)) {
            throw (new ModelNotFoundException)->setModel(TicketAttachment::class);
        }

        return $this->attachments->serve($attachment);
    }

    /**
     * Answers the person (public) or adds a staff-only note (internal). A public reply is the first response for the SLA, moves
     * the ticket to "waiting for the user", assigns an unassigned ticket to the replier and sends a notification.
     *
     * @throws BusinessRuleViolation `ticket_empty_reply`, `ticket_finished`
     */
    public function reply(SupportTicket $ticket, Admin $actor, StaffReplyData $data): TicketMessage
    {
        $body = trim($data->body);

        if ($body === '') {
            throw BusinessRuleViolation::make('ticket_empty_reply', 'body');
        }

        $checked = $this->attachments->inspectAll($data->attachments);
        $paths = [];

        try {
            $message = $this->db->transaction(function () use ($ticket, $actor, $data, $body, $checked, &$paths): TicketMessage {
                $locked = $this->lock($ticket);

                if ($locked->status === TicketStatus::Closed || ($locked->status === TicketStatus::Resolved && $data->visibility === TicketVisibility::Public)) {
                    throw BusinessRuleViolation::make('ticket_finished');
                }

                $message = $this->thread->createMessage([
                    'ticket_id' => $locked->id,
                    'visibility' => $data->visibility,
                    'author_type' => ActorType::Admin,
                    'author_admin_id' => $actor->id,
                    'macro_id' => $data->macroId,
                    'body' => $body,
                ]);
                $paths = $this->attachments->store($locked, $message->id, $checked);

                if ($data->visibility === TicketVisibility::Internal) {
                    $this->thread->recordEvent($locked, TicketEventType::InternalNote->value, ActorType::Admin->value, $actor->id, ['message' => $message->uuid]);

                    return $message;
                }

                $before = ['status' => $locked->status->value, 'assigned_admin_id' => $locked->assigned_admin_id];
                $attributes = ['last_message_at' => now(), 'last_staff_reply_at' => now()];

                if ($locked->first_response_at === null) {
                    $attributes['first_response_at'] = now();
                }

                if ($locked->assigned_admin_id === null) {
                    $attributes += ['assigned_admin_id' => $actor->id, 'assigned_at' => now()];
                }

                if (in_array($locked->status, [TicketStatus::Open, TicketStatus::InProgress], true)) {
                    $attributes['status'] = TicketStatus::WaitingUser;
                }

                $this->tickets->update($locked, $attributes);
                $this->thread->recordEvent($locked, TicketEventType::Reply->value, ActorType::Admin->value, $actor->id, ['message' => $message->uuid, 'macro_id' => $data->macroId]);

                if (isset($attributes['status'])) {
                    $this->thread->recordEvent($locked, TicketEventType::StatusChanged->value, ActorType::Admin->value, $actor->id, ['from' => $before['status'], 'to' => $attributes['status']->value]);
                }

                $this->audit->record(AuditAction::MessageSent, $locked, $actor, before: $before, after: ['status' => $locked->status->value, 'assigned_admin_id' => $locked->assigned_admin_id], meta: ['message' => $message->uuid, 'visibility' => 'public']);

                return $message;
            });
        } catch (Throwable $e) {
            $this->attachments->discard($paths);

            throw $e;
        }

        if ($message->visibility === TicketVisibility::Public) {
            $this->notifier->replied($ticket->refresh(), $message->uuid);
        }

        return $message->load('attachments');
    }

    /**
     * @throws BusinessRuleViolation `ticket_assignee_invalid`, `ticket_finished`
     */
    public function assign(SupportTicket $ticket, Admin $actor, ?int $adminId, ?string $reason = null): SupportTicket
    {
        return $this->db->transaction(function () use ($ticket, $actor, $adminId, $reason): SupportTicket {
            $locked = $this->lock($ticket);
            $this->assertActive($locked);

            if ($adminId !== null && ! in_array($adminId, array_map(static fn (Admin $admin): int => $admin->id, $this->assigner->eligible($locked->group)), true)) {
                throw BusinessRuleViolation::make('ticket_assignee_invalid', 'admin_id');
            }

            $before = ['assigned_admin_id' => $locked->assigned_admin_id];
            $this->tickets->update($locked, ['assigned_admin_id' => $adminId, 'assigned_at' => $adminId === null ? null : now()]);
            $this->thread->recordEvent($locked, ($adminId === null ? TicketEventType::Unassigned : TicketEventType::Assigned)->value, ActorType::Admin->value, $actor->id, ['from' => $before['assigned_admin_id'], 'to' => $adminId, 'reason' => $reason]);
            $this->audit->record(AuditAction::Assigned, $locked, $actor, before: $before, after: ['assigned_admin_id' => $adminId], reason: $reason);

            return $locked;
        });
    }

    /**
     * The agent takes an unassigned ticket (or one in their queue).
     */
    public function claim(SupportTicket $ticket, Admin $actor): SupportTicket
    {
        return $this->assign($ticket, $actor, $actor->id);
    }

    /**
     * Moves the ticket to the finance, safety or operations team. A reason is mandatory and ends up in the audit log, the
     * ticket history and as an internal note; the team's least-loaded agent gets it. Escalating a safety ticket never lowers
     * its priority.
     *
     * @throws BusinessRuleViolation `ticket_escalation_invalid`, `ticket_finished`
     */
    public function escalate(SupportTicket $ticket, Admin $actor, TicketGroup $target, string $reason): SupportTicket
    {
        if (! in_array($target, TicketGroup::escalationTargets(), true)) {
            throw BusinessRuleViolation::make('ticket_escalation_invalid', 'group');
        }

        return $this->db->transaction(function () use ($ticket, $actor, $target, $reason): SupportTicket {
            $locked = $this->lock($ticket);
            $this->assertActive($locked);

            if ($locked->group === $target && $locked->status === TicketStatus::Escalated) {
                throw BusinessRuleViolation::make('ticket_escalation_invalid', 'group');
            }

            $agent = $this->assigner->pick($target);
            $priority = $target === TicketGroup::Safety ? TicketPriority::Urgent : $locked->priority;
            $before = ['group' => $locked->group->value, 'status' => $locked->status->value, 'assigned_admin_id' => $locked->assigned_admin_id, 'priority' => $locked->priority->value];

            $this->tickets->update($locked, [
                'group' => $target,
                'status' => TicketStatus::Escalated,
                'priority' => $priority,
                'assigned_admin_id' => $agent?->id,
                'assigned_at' => $agent === null ? null : now(),
                'escalated_at' => now(),
                'sla_due_at' => $locked->first_response_at === null && $priority !== $locked->priority ? $this->sla->dueAt($priority, $locked->created_at) : $locked->sla_due_at,
            ]);
            $this->thread->createMessage([
                'ticket_id' => $locked->id,
                'visibility' => TicketVisibility::Internal,
                'author_type' => ActorType::Admin,
                'author_admin_id' => $actor->id,
                'body' => __('admin.support_tickets.escalation_note', ['group' => $target->label(), 'reason' => $reason]),
            ]);
            $after = ['group' => $target->value, 'status' => TicketStatus::Escalated->value, 'assigned_admin_id' => $agent?->id, 'priority' => $priority->value];
            $this->thread->recordEvent($locked, TicketEventType::Escalated->value, ActorType::Admin->value, $actor->id, ['from' => $before, 'to' => $after, 'reason' => $reason]);
            $this->audit->record(AuditAction::Escalated, $locked, $actor, before: $before, after: $after, reason: $reason);

            return $locked;
        });
    }

    /**
     * Resolves the ticket. The reason is shown to the person as the resolution and kept in the audit log. Resolving changes
     * no money; the first response is stamped if nobody answered yet.
     *
     * @throws BusinessRuleViolation `ticket_finished`
     */
    public function resolve(SupportTicket $ticket, Admin $actor, string $reason): SupportTicket
    {
        $message = null;

        $resolved = $this->db->transaction(function () use ($ticket, $actor, $reason, &$message): SupportTicket {
            $locked = $this->lock($ticket);
            $this->assertActive($locked);
            $before = ['status' => $locked->status->value];

            $message = $this->thread->createMessage([
                'ticket_id' => $locked->id,
                'visibility' => TicketVisibility::Public,
                'author_type' => ActorType::Admin,
                'author_admin_id' => $actor->id,
                'body' => $reason,
            ]);
            $this->tickets->update($locked, [
                'status' => TicketStatus::Resolved,
                'resolved_at' => now(),
                'resolution_reason' => $reason,
                'first_response_at' => $locked->first_response_at ?? now(),
                'last_message_at' => now(),
                'last_staff_reply_at' => now(),
                'assigned_admin_id' => $locked->assigned_admin_id ?? $actor->id,
                'assigned_at' => $locked->assigned_admin_id === null ? now() : $locked->assigned_at,
            ]);
            $this->thread->recordEvent($locked, TicketEventType::Resolved->value, ActorType::Admin->value, $actor->id, ['from' => $before['status'], 'reason' => $reason]);
            $this->audit->record(AuditAction::Resolved, $locked, $actor, before: $before, after: ['status' => TicketStatus::Resolved->value], reason: $reason);

            return $locked;
        });

        $this->notifier->statusChanged($resolved, TicketStatus::Resolved->value, (string) $message?->uuid);

        return $resolved;
    }

    /**
     * @throws BusinessRuleViolation `ticket_not_resolved`
     */
    public function reopen(SupportTicket $ticket, Admin $actor, string $reason): SupportTicket
    {
        $reopened = $this->db->transaction(function () use ($ticket, $actor, $reason): SupportTicket {
            $locked = $this->lock($ticket);

            if (! in_array($locked->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
                throw BusinessRuleViolation::make('ticket_not_resolved');
            }

            $before = ['status' => $locked->status->value];
            $this->tickets->update($locked, [
                'status' => TicketStatus::Open,
                'resolved_at' => null,
                'closed_at' => null,
                'resolution_reason' => null,
                'sla_due_at' => $this->sla->dueAt($locked->priority),
                'first_response_at' => null,
                'sla_breached_at' => null,
            ]);
            $this->thread->recordEvent($locked, TicketEventType::Reopened->value, ActorType::Admin->value, $actor->id, ['from' => $before['status'], 'reason' => $reason]);
            $this->audit->record(AuditAction::Reopened, $locked, $actor, before: $before, after: ['status' => TicketStatus::Open->value], reason: $reason);

            return $locked;
        });

        $this->notifier->statusChanged($reopened, TicketStatus::Open->value, (string) $reopened->updated_at->timestamp);

        return $reopened;
    }

    /**
     * @throws BusinessRuleViolation `ticket_finished`
     */
    public function changePriority(SupportTicket $ticket, Admin $actor, TicketPriority $priority): SupportTicket
    {
        return $this->db->transaction(function () use ($ticket, $actor, $priority): SupportTicket {
            $locked = $this->lock($ticket);
            $this->assertActive($locked);

            if ($locked->priority === $priority) {
                return $locked;
            }

            $before = ['priority' => $locked->priority->value, 'sla_due_at' => $locked->sla_due_at?->toIso8601String()];
            $this->tickets->update($locked, [
                'priority' => $priority,
                'sla_due_at' => $locked->first_response_at === null ? $this->sla->dueAt($priority, $locked->created_at) : $locked->sla_due_at,
            ]);
            $this->thread->recordEvent($locked, TicketEventType::PriorityChanged->value, ActorType::Admin->value, $actor->id, ['from' => $before['priority'], 'to' => $priority->value]);
            $this->audit->record(AuditAction::Updated, $locked, $actor, before: $before, after: ['priority' => $priority->value, 'sla_due_at' => $locked->sla_due_at?->toIso8601String()]);

            return $locked;
        });
    }

    /**
     * Links a payment or payout to the ticket (only that: the money flows stay in finance). The payment must belong to the
     * ticket's booking or to the person; the payout to the person's provider profile.
     *
     * @throws BusinessRuleViolation `ticket_link_invalid`
     */
    public function linkFinance(SupportTicket $ticket, Admin $actor, ?string $paymentUuid, ?string $payoutUuid): SupportTicket
    {
        return $this->db->transaction(function () use ($ticket, $actor, $paymentUuid, $payoutUuid): SupportTicket {
            $locked = $this->lock($ticket);
            $changes = [];

            if ($paymentUuid !== null) {
                $changes['payment_id'] = $this->tickets->resolveOwnedPaymentId($locked->user_id, $paymentUuid) ?? throw BusinessRuleViolation::make('ticket_link_invalid', 'payment_id');
            }

            if ($payoutUuid !== null) {
                $profileId = $this->tickets->providerProfileIdOfUser($locked->user_id);
                $changes['payout_request_id'] = ($profileId === null ? null : $this->tickets->resolveOwnedPayoutId($profileId, $payoutUuid)) ?? throw BusinessRuleViolation::make('ticket_link_invalid', 'payout_id');
            }

            if ($changes === []) {
                return $locked;
            }

            $before = ['payment_id' => $locked->payment_id, 'payout_request_id' => $locked->payout_request_id];
            $this->tickets->update($locked, $changes);
            $this->thread->recordEvent($locked, TicketEventType::Linked->value, ActorType::Admin->value, $actor->id, $changes);
            $this->audit->record(AuditAction::Linked, $locked, $actor, before: $before, after: $changes);

            return $locked;
        });
    }

    /**
     * Scheduler: flags active tickets whose first response is overdue. Returns how many were flagged.
     */
    public function flagSlaBreaches(int $batch = 200): int
    {
        $count = 0;

        foreach ($this->tickets->slaOverdue(CarbonImmutable::now(), $batch) as $ticket) {
            $this->db->transaction(function () use ($ticket): void {
                $locked = $this->lock($ticket);

                if ($locked->sla_breached_at !== null || $locked->first_response_at !== null || ! $locked->status->isActive()) {
                    return;
                }

                $this->tickets->update($locked, ['sla_breached_at' => now()]);
                $this->thread->recordEvent($locked, TicketEventType::SlaBreached->value, ActorType::System->value, null, ['due_at' => $locked->sla_due_at?->toIso8601String()]);
            });
            $count++;
        }

        return $count;
    }

    /**
     * Scheduler: closes tickets that stayed resolved for `support.tickets.auto_close_days`.
     */
    public function autoCloseResolved(int $batch = 200): int
    {
        $count = 0;
        $cutoff = CarbonImmutable::now()->subDays((int) config('support.tickets.auto_close_days', 14));

        foreach ($this->tickets->resolvedBefore($cutoff, $batch) as $ticket) {
            $this->db->transaction(function () use ($ticket): void {
                $locked = $this->lock($ticket);

                if ($locked->status !== TicketStatus::Resolved) {
                    return;
                }

                $this->tickets->update($locked, ['status' => TicketStatus::Closed, 'closed_at' => now()]);
                $this->thread->recordEvent($locked, TicketEventType::StatusChanged->value, ActorType::System->value, null, ['from' => TicketStatus::Resolved->value, 'to' => TicketStatus::Closed->value]);
            });
            $count++;
        }

        return $count;
    }

    private function lock(SupportTicket $ticket): SupportTicket
    {
        return $this->tickets->lockById($ticket->id) ?? throw (new ModelNotFoundException)->setModel(SupportTicket::class);
    }

    private function assertActive(SupportTicket $ticket): void
    {
        if (! $ticket->status->isActive()) {
            throw BusinessRuleViolation::make('ticket_finished');
        }
    }
}
