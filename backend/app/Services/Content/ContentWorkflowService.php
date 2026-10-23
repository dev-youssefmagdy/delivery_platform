<?php

namespace App\Services\Content;

use App\Enums\Common\AuditAction;
use App\Enums\Content\ContentType;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\WorkflowAction;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ContentMenu;
use App\Repositories\Contracts\Content\MenuRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The publishing workflow of every CMS entity: draft -> in_review -> scheduled | published -> archived.
 *
 * - Submitting needs `content.manage`; reviewing, publishing, scheduling, reverting, archiving and restoring need
 *   `content.publish` and a reason (stored in the audit log and the revision).
 * - Every transition runs under a row lock and re-checks the current status, so double clicks and the scheduled job
 *   cannot publish twice. `publishDue()` is idempotent.
 * - Completeness (Arabic, optional English, targets) is checked before content leaves the draft state.
 */
final class ContentWorkflowService
{
    public function __construct(
        private readonly ContentRepositoryRegistry $repositories,
        private readonly ContentPublishGuard $guard,
        private readonly ContentRevisionService $revisions,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly ContentCache $cache,
        private readonly MenuRepositoryInterface $menus,
    ) {}

    public function transition(Model $content, WorkflowAction $action, Admin $actor, ?string $reason = null, ?CarbonInterface $scheduledAt = null): Model
    {
        if (! $actor->can($action->permission()->value)) {
            throw new AuthorizationException;
        }

        if ($action->reasonRequired() && trim((string) $reason) === '') {
            throw BusinessRuleViolation::make('content_reason_required', 'reason');
        }

        $type = ContentType::forModel($content);

        $result = $this->db->transaction(function () use ($type, $content, $action, $actor, $reason, $scheduledAt): Model {
            $locked = $this->repositories->for($type)->lockById($content->getKey()) ?? throw (new ModelNotFoundException)->setModel($type->modelClass());
            $from = $this->status($locked);

            if (! in_array($from, $action->allowedFrom(), true)) {
                throw BusinessRuleViolation::make('content_transition_invalid');
            }

            if (in_array($action, [WorkflowAction::Submit, WorkflowAction::Publish, WorkflowAction::Schedule], true)) {
                $this->guard->assertReady($locked);
            }

            if ($action === WorkflowAction::Schedule) {
                $this->assertScheduleTime($scheduledAt);
            }

            $this->apply($type, $locked, $action, $actor, $reason, $scheduledAt);
            $this->audit->record(
                $action === WorkflowAction::Publish ? AuditAction::Published : AuditAction::StatusChanged,
                $locked,
                $actor,
                ['status' => $from->value],
                ['status' => $this->status($locked)->value, 'scheduled_at' => $locked->getAttribute('scheduled_at')?->toIso8601String()],
                $reason,
                ['workflow_action' => $action->value],
            );
            $this->revisions->capture($locked, $actor, $action->value, $reason);

            return $locked;
        });

        $this->cache->bump();

        return $result;
    }

    /**
     * Publishes every scheduled item whose time has come. Safe to run any number of times or concurrently: each item is
     * locked and re-checked, and already published items are skipped.
     *
     * @return int number of items published by this run
     */
    public function publishDue(?CarbonInterface $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $published = 0;

        foreach (ContentType::cases() as $type) {
            foreach ($this->repositories->for($type)->dueScheduledIds($now) as $id) {
                $published += $this->publishOneDue($type, $id, $now) ? 1 : 0;
            }
        }

        if ($published > 0) {
            $this->cache->bump();
        }

        return $published;
    }

    private function publishOneDue(ContentType $type, int $id, CarbonInterface $now): bool
    {
        return $this->db->transaction(function () use ($type, $id, $now): bool {
            $locked = $this->repositories->for($type)->lockById($id);
            $scheduledAt = $locked?->getAttribute('scheduled_at');

            if ($locked === null || $this->status($locked) !== ContentWorkflowStatus::Scheduled || ! $scheduledAt instanceof CarbonInterface || $scheduledAt->gt($now)) {
                return false;
            }

            $this->guard->assertReady($locked);
            $this->apply($type, $locked, WorkflowAction::Publish, null, null, null);
            $this->audit->record(
                AuditAction::Published,
                $locked,
                null,
                ['status' => ContentWorkflowStatus::Scheduled->value],
                ['status' => ContentWorkflowStatus::Published->value],
                'scheduled_publish',
                ['workflow_action' => 'scheduled_publish', 'scheduled_at' => $scheduledAt->toIso8601String()],
            );
            $this->revisions->capture($locked, null, 'scheduled_publish', 'scheduled_publish');

            return true;
        });
    }

    private function apply(ContentType $type, Model $locked, WorkflowAction $action, ?Admin $actor, ?string $reason, ?CarbonInterface $scheduledAt): void
    {
        $changes = [
            'status' => $action->target(),
            'lock_version' => (int) $locked->getAttribute('lock_version') + 1,
        ];

        if ($action === WorkflowAction::Publish) {
            $changes += ['published_at' => now(), 'published_by_admin_id' => $actor?->getKey(), 'scheduled_at' => null];
            $this->supersedeMenus($locked, $actor, $reason);
        } elseif ($action === WorkflowAction::Schedule) {
            $changes += ['scheduled_at' => $scheduledAt];
        } elseif (in_array($action, [WorkflowAction::Revert, WorkflowAction::Archive, WorkflowAction::Reject], true)) {
            $changes += ['scheduled_at' => null];
        }

        $this->repositories->for($type)->update($locked, $changes);
    }

    /**
     * A menu location has one live menu: publishing a menu archives the previous one.
     */
    private function supersedeMenus(Model $locked, ?Admin $actor, ?string $reason): void
    {
        if (! $locked instanceof ContentMenu) {
            return;
        }

        foreach ($this->menus->lockPublishedAt($locked->location, $locked->id) as $previous) {
            $this->menus->update($previous, ['status' => ContentWorkflowStatus::Archived, 'lock_version' => $previous->lock_version + 1]);
            $this->audit->record(
                AuditAction::StatusChanged,
                $previous,
                $actor,
                ['status' => ContentWorkflowStatus::Published->value],
                ['status' => ContentWorkflowStatus::Archived->value],
                $reason ?? 'scheduled_publish',
                ['superseded_by' => $locked->id],
            );
            $this->revisions->capture($previous, $actor, 'superseded', $reason);
        }
    }

    private function assertScheduleTime(?CarbonInterface $scheduledAt): void
    {
        if ($scheduledAt === null || $scheduledAt->lt(now()->addSeconds((int) config('content.schedule_min_lead_seconds', 60)))) {
            throw BusinessRuleViolation::make('content_schedule_in_past', 'scheduled_at');
        }
    }

    private function status(Model $content): ContentWorkflowStatus
    {
        $status = $content->getAttribute('status');

        return $status instanceof ContentWorkflowStatus ? $status : ContentWorkflowStatus::from((string) $status);
    }
}
