<?php

namespace App\Http\Controllers\Api\V1\Reviews;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reviews\AppealRatingRequest;
use App\Http\Requests\Api\V1\Reviews\ReportRatingRequest;
use App\Http\Resources\Api\V1\Trip\RatingResource;
use App\Models\BookingRating;
use App\Models\RatingAppeal;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Services\Reviews\RatingAppealService;
use App\Services\Reviews\RatingReportService;
use App\Support\Http\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Report and appeal of ratings from either app (SRS §15). Everything is scoped to the signed-in person and app; another person's rating
 * is a 404. Reachable by an account in any standing. Staff decisions happen in the admin Reviews module.
 */
final class RatingModerationController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly RatingReportService $reports,
        private readonly RatingAppealService $appeals,
        private readonly RatingRepositoryInterface $ratings,
        private readonly RatingModerationRepositoryInterface $moderation,
    ) {}

    /**
     * A rating the person WROTE (so a notification about a hidden review can be opened): the review, its moderation status and their appeal.
     */
    public function show(Request $request, string $rating): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $partyId = $this->moderation->partyIdOfUser($user, $app->value);
        $found = $this->ratings->findByUuid($rating);

        if ($partyId === null || ! $found instanceof BookingRating || $found->rater_type !== $app->value || $found->rater_id !== $partyId) {
            throw (new ModelNotFoundException)->setModel(BookingRating::class);
        }

        $appeal = $this->moderation->findAppealOf($found->id, $app->value, $partyId);

        return ApiResponse::success(data: (new RatingResource($found))->resolve() + ['appeal' => $appeal === null ? null : $this->appealData($appeal, $found->uuid)]);
    }

    public function report(ReportRatingRequest $request, string $rating): JsonResponse
    {
        $this->reports->report($this->authenticatedUser($request), $this->clientApp($request), $rating, $request->reason(), $request->details());

        return ApiResponse::created(__('reputation.api.report_received'));
    }

    public function appeal(AppealRatingRequest $request, string $rating): JsonResponse
    {
        $appeal = $this->appeals->open($this->authenticatedUser($request), $this->clientApp($request), $rating, (string) $request->validated('message'));

        return ApiResponse::created(__('reputation.api.appeal_received'), $this->appealData($appeal, $rating));
    }

    /**
     * The person's own appeals and where each stands (the decision reason stays internal).
     */
    public function appeals(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $appType = $this->clientApp($request)->value;
        $items = $this->moderation->appealsOfUser($user->id)->filter(static fn (RatingAppeal $appeal): bool => $appeal->appellant_type === $appType)
            ->map(fn (RatingAppeal $appeal): array => $this->appealData($appeal, $appeal->rating->uuid))->values()->all();

        return ApiResponse::success(data: ['items' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    private function appealData(RatingAppeal $appeal, string $ratingUuid): array
    {
        return [
            'id' => $appeal->uuid,
            'rating_id' => $ratingUuid,
            'grounds' => ['code' => $appeal->grounds->value, 'label' => $appeal->grounds->label()],
            'status' => ['code' => $appeal->status->value, 'label' => $appeal->status->label()],
            'created_at' => $appeal->created_at->toIso8601ZuluString(),
            'decided_at' => $appeal->decided_at?->toIso8601ZuluString(),
        ];
    }
}
