<?php

namespace App\Services\Reviews;

use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationEvent;
use App\Enums\Reviews\ModerationActionType;
use App\Enums\Reviews\ModerationStatus;
use App\Enums\Reviews\RatingReportStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\BookingRating;
use App\Models\RatingAppeal;
use App\Models\RatingReport;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\RatingAggregationService;
use App\Services\Notification\PartyNotifier;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Moderation of ratings (SRS §15, §16.12, ADR-173). A decision only changes the VISIBILITY of a rating: hide, restore, or mark it
 * under review (not counted while it waits). The stars, tags and text are never written here (and the model refuses such an update),
 * so an administrator can never rewrite a review. Every decision needs a reason, writes an append-only `moderation_actions` row and an
 * audit entry, settles the open reports and recomputes the ratee's public average from the rows (idempotent: the same state always
 * yields the same average).
 */
final class RatingModerationService
{
    public function __construct(
        private readonly RatingRepositoryInterface $ratings,
        private readonly RatingModerationRepositoryInterface $moderation,
        private readonly RatingAggregationService $aggregation,
        private readonly PartyNotifier $party,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `rating_moderation_noop`
     */
    public function hide(BookingRating $rating, Admin $actor, string $reason, ?RatingAppeal $appeal = null): BookingRating
    {
        return $this->change($rating, $actor, ModerationActionType::Hide, ModerationStatus::Hidden, $reason, $appeal);
    }

    /**
     * @throws BusinessRuleViolation `rating_moderation_noop`
     */
    public function restore(BookingRating $rating, Admin $actor, string $reason, ?RatingAppeal $appeal = null): BookingRating
    {
        return $this->change($rating, $actor, ModerationActionType::Restore, ModerationStatus::Visible, $reason, $appeal);
    }

    /**
     * Takes a visible rating out of the average while staff look at it.
     *
     * @throws BusinessRuleViolation `rating_moderation_noop`
     */
    public function markUnderReview(BookingRating $rating, Admin $actor, string $reason): BookingRating
    {
        return $this->change($rating, $actor, ModerationActionType::Review, ModerationStatus::UnderReview, $reason, null);
    }

    /**
     * The staff closes one report without touching the rating.
     *
     * @throws BusinessRuleViolation `rating_report_handled`
     */
    public function dismissReport(RatingReport $report, Admin $actor, string $reason): RatingReport
    {
        return $this->db->transaction(function () use ($report, $actor, $reason): RatingReport {
            $locked = $this->moderation->lockReport($report->id) ?? throw (new ModelNotFoundException)->setModel(RatingReport::class);

            if ($locked->status !== RatingReportStatus::Open) {
                throw BusinessRuleViolation::make('rating_report_handled');
            }

            $this->moderation->updateReport($locked, ['status' => RatingReportStatus::Dismissed, 'handled_by_admin_id' => $actor->id, 'handled_at' => now(), 'resolution_note' => $reason]);
            $rating = $locked->rating;
            $this->ratings->syncReportsCount($rating);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, before: ['status' => RatingReportStatus::Open->value], after: ['status' => RatingReportStatus::Dismissed->value], reason: $reason, meta: ['rating' => $rating->uuid]);

            return $locked;
        });
    }

    private function change(BookingRating $rating, Admin $actor, ModerationActionType $action, ModerationStatus $target, string $reason, ?RatingAppeal $appeal): BookingRating
    {
        $updated = $this->db->transaction(function () use ($rating, $actor, $action, $target, $reason, $appeal): BookingRating {
            $locked = $this->ratings->lockById($rating->id) ?? throw (new ModelNotFoundException)->setModel(BookingRating::class);
            $from = $locked->moderation_status;

            if ($from === $target) {
                throw BusinessRuleViolation::make('rating_moderation_noop');
            }

            $this->ratings->setModeration($locked, $target);
            $this->moderation->createAction([
                'rating_id' => $locked->id, 'action' => $action, 'from_status' => $from, 'to_status' => $target, 'reason' => $reason,
                'admin_id' => $actor->id, 'appeal_id' => $appeal?->id,
            ]);

            if ($action === ModerationActionType::Hide) {
                $this->moderation->settleOpenReports($locked->id, RatingReportStatus::Actioned->value, $actor->id, $reason);
            } elseif ($action === ModerationActionType::Restore) {
                $this->moderation->settleOpenReports($locked->id, RatingReportStatus::Dismissed->value, $actor->id, $reason);
            }

            $this->ratings->syncReportsCount($locked);
            $this->aggregation->recompute($locked->ratee_type, $locked->ratee_id);
            $this->audit->record(
                $action === ModerationActionType::Restore ? AuditAction::Restored : AuditAction::StatusChanged,
                $locked,
                $actor,
                before: ['moderation_status' => $from->value],
                after: ['moderation_status' => $target->value],
                reason: $reason,
                meta: ['action' => $action->value, 'appeal' => $appeal?->uuid],
            );

            return $locked;
        });

        if ($target === ModerationStatus::Hidden && $appeal === null) {
            $this->notifyRater($updated);
        }

        return $updated;
    }

    /**
     * Tells the person who wrote the review that it was hidden and that they can appeal. A failure never undoes the decision.
     */
    private function notifyRater(BookingRating $rating): void
    {
        try {
            $user = $this->moderation->userOfParty($rating->rater_type, $rating->rater_id);

            if ($user !== null && ! $user->isAnonymised()) {
                $app = $rating->rater_type === ActorType::Provider->value ? ClientApp::Provider : ClientApp::Customer;
                $this->party->user($user, $app, NotificationEvent::ReviewHidden, $rating->uuid.':'.($rating->moderated_at->timestamp ?? 0), [], $rating->uuid);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
