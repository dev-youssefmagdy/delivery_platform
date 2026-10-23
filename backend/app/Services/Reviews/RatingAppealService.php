<?php

namespace App\Services\Reviews;

use App\Enums\Common\AuditAction;
use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationEvent;
use App\Enums\Reviews\AppealGrounds;
use App\Enums\Reviews\AppealStatus;
use App\Enums\Reviews\ModerationStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\BookingRating;
use App\Models\RatingAppeal;
use App\Models\User;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\PartyNotifier;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * Appeals (SRS §15 "review, and appeal"). Two kinds: the writer of a review that moderation HID contests the decision, and the person
 * a LOW rating is about (up to `risk.reviews.low_rating_max_stars`) asks for a second look. Both are an appeal record that staff decide
 * in the Reviews module: upholding restores a hidden review, or hides an unfair one, through RatingModerationService; rejecting changes
 * nothing. One appeal per person and rating, inside `risk.reviews.appeal_window_days`. The appellant is told the outcome.
 */
final class RatingAppealService
{
    public function __construct(
        private readonly RatingRepositoryInterface $ratings,
        private readonly RatingModerationRepositoryInterface $moderation,
        private readonly RatingModerationService $moderationService,
        private readonly PartyNotifier $party,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `rating_appeal_not_allowed`, `rating_appeal_window_closed`
     * @throws ConflictException `rating_appeal_exists`
     */
    public function open(User $user, ClientApp $app, string $ratingUuid, string $message): RatingAppeal
    {
        $partyId = $this->moderation->partyIdOfUser($user, $app->value);
        $rating = $this->ratings->findByUuid($ratingUuid);

        if ($partyId === null || ! $rating instanceof BookingRating) {
            throw (new ModelNotFoundException)->setModel(BookingRating::class);
        }

        $isRater = $rating->rater_type === $app->value && $rating->rater_id === $partyId;
        $isRatee = $rating->ratee_type === $app->value && $rating->ratee_id === $partyId;

        if (! $isRater && ! $isRatee) {
            throw (new ModelNotFoundException)->setModel(BookingRating::class);
        }

        $grounds = $this->grounds($rating, $isRater);

        if ($grounds === null) {
            throw BusinessRuleViolation::make('rating_appeal_not_allowed');
        }

        $since = $grounds === AppealGrounds::HiddenReview ? ($rating->moderated_at ?? $rating->created_at) : $rating->created_at;

        if ($since->lt(now()->subDays((int) config('risk.reviews.appeal_window_days', 30)))) {
            throw BusinessRuleViolation::make('rating_appeal_window_closed');
        }

        if ($this->moderation->findAppealOf($rating->id, $app->value, $partyId) !== null) {
            throw ConflictException::make('rating_appeal_exists');
        }

        try {
            return $this->moderation->createAppeal([
                'rating_id' => $rating->id, 'appellant_type' => $app->value, 'appellant_id' => $partyId, 'appellant_user_id' => $user->id,
                'grounds' => $grounds, 'message' => trim($message), 'status' => AppealStatus::Open,
            ]);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'rating_appeals_one_per_person')) {
                throw ConflictException::make('rating_appeal_exists');
            }

            throw $e;
        }
    }

    /**
     * Staff decision. Upholding changes the rating's visibility through the moderation service (same reason, linked to the appeal).
     *
     * @throws BusinessRuleViolation `rating_appeal_decided`, `rating_moderation_noop`
     */
    public function decide(RatingAppeal $appeal, Admin $actor, bool $uphold, string $reason): RatingAppeal
    {
        $decided = $this->db->transaction(function () use ($appeal, $actor, $uphold, $reason): RatingAppeal {
            $locked = $this->moderation->lockAppeal($appeal->id) ?? throw (new ModelNotFoundException)->setModel(RatingAppeal::class);

            if ($locked->status !== AppealStatus::Open) {
                throw BusinessRuleViolation::make('rating_appeal_decided');
            }

            $rating = $locked->rating;

            if ($uphold) {
                // Hidden review: the appeal is right, show it again. Unfair low rating: it is right, hide it. Already in the target state: nothing to change.
                $target = $locked->grounds === AppealGrounds::HiddenReview ? ModerationStatus::Visible : ModerationStatus::Hidden;

                if ($rating->moderation_status !== $target) {
                    $target === ModerationStatus::Visible
                        ? $this->moderationService->restore($rating, $actor, $reason, $locked)
                        : $this->moderationService->hide($rating, $actor, $reason, $locked);
                }
            }

            $this->moderation->updateAppeal($locked, [
                'status' => $uphold ? AppealStatus::Upheld : AppealStatus::Rejected, 'decided_by_admin_id' => $actor->id, 'decided_at' => now(), 'decision_reason' => $reason,
            ]);
            $this->audit->record($uphold ? AuditAction::Approved : AuditAction::Rejected, $locked, $actor, before: ['status' => AppealStatus::Open->value], after: ['status' => $locked->status->value], reason: $reason, meta: ['rating' => $rating->uuid, 'grounds' => $locked->grounds->value]);

            return $locked;
        });

        $this->notifyAppellant($decided);

        return $decided;
    }

    /**
     * What a person may appeal: their hidden review, or a low rating about them that is still counted or under review.
     */
    private function grounds(BookingRating $rating, bool $isRater): ?AppealGrounds
    {
        if ($isRater && $rating->moderation_status === ModerationStatus::Hidden) {
            return AppealGrounds::HiddenReview;
        }

        if (! $isRater && $rating->moderation_status !== ModerationStatus::Hidden && $rating->stars <= (int) config('risk.reviews.low_rating_max_stars', 2)) {
            return AppealGrounds::UnfairRating;
        }

        return null;
    }

    private function notifyAppellant(RatingAppeal $appeal): void
    {
        try {
            $user = $this->moderation->userOfParty($appeal->appellant_type, $appeal->appellant_id);

            if ($user !== null && ! $user->isAnonymised()) {
                $outcome = $appeal->status->value;
                $this->party->user(
                    $user,
                    $appeal->appellant_type === ClientApp::Provider->value ? ClientApp::Provider : ClientApp::Customer,
                    NotificationEvent::ReviewAppealDecided,
                    $appeal->uuid,
                    ['outcome' => static fn (string $locale): string => (string) __('reputation.appeal_outcome.'.$outcome, [], $locale)],
                    $appeal->rating->uuid,
                );
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
