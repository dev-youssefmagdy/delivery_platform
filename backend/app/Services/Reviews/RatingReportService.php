<?php

namespace App\Services\Reviews;

use App\Enums\Identity\ClientApp;
use App\Enums\Reviews\ModerationStatus;
use App\Enums\Reviews\RatingReportReason;
use App\Enums\Reviews\RatingReportStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\BookingRating;
use App\Models\RatingReport;
use App\Models\User;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

/**
 * A person reports a rating written about them (SRS §15 "report flag"). Only the rated side can report, a hidden rating cannot be
 * reported again, and one person reports a rating once. A report never changes the average: it queues the rating for moderation.
 */
final class RatingReportService
{
    public function __construct(
        private readonly RatingRepositoryInterface $ratings,
        private readonly RatingModerationRepositoryInterface $moderation,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `rating_not_reportable`
     * @throws ConflictException `rating_report_exists`
     */
    public function report(User $user, ClientApp $app, string $ratingUuid, RatingReportReason $reason, ?string $details): RatingReport
    {
        $partyId = $this->moderation->partyIdOfUser($user, $app->value);
        $rating = $this->ratings->findByUuid($ratingUuid);

        // Another person's rating is a 404, never a hint that it exists.
        if ($partyId === null || ! $rating instanceof BookingRating || $rating->ratee_type !== $app->value || $rating->ratee_id !== $partyId) {
            throw (new ModelNotFoundException)->setModel(BookingRating::class);
        }

        if ($rating->moderation_status === ModerationStatus::Hidden) {
            throw BusinessRuleViolation::make('rating_not_reportable');
        }

        if ($this->moderation->findReportOf($rating->id, $app->value, $partyId) !== null) {
            throw ConflictException::make('rating_report_exists');
        }

        try {
            return $this->db->transaction(function () use ($rating, $user, $app, $partyId, $reason, $details): RatingReport {
                $report = $this->moderation->createReport([
                    'rating_id' => $rating->id, 'reporter_type' => $app->value, 'reporter_id' => $partyId, 'reporter_user_id' => $user->id,
                    'reason' => $reason, 'details' => $details === null || trim($details) === '' ? null : trim($details), 'status' => RatingReportStatus::Open,
                ]);
                $this->ratings->syncReportsCount($rating);

                return $report;
            });
        } catch (QueryException $e) {
            // The unique index decides a race between two submissions.
            if (str_contains($e->getMessage(), 'rating_reports_one_per_person')) {
                throw ConflictException::make('rating_report_exists');
            }

            throw $e;
        }
    }
}
