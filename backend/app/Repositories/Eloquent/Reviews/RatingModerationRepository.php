<?php

namespace App\Repositories\Eloquent\Reviews;

use App\DTOs\Reviews\ModerationQueueFilterData;
use App\Enums\Common\ActorType;
use App\Enums\Reviews\RatingReportStatus;
use App\Models\ModerationAction;
use App\Models\RatingAppeal;
use App\Models\ProviderProfile;
use App\Models\RatingReport;
use App\Models\User;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class RatingModerationRepository implements RatingModerationRepositoryInterface
{
    public const SCRUBBED_TEXT = '';

    public function createReport(array $attributes): RatingReport
    {
        return RatingReport::query()->create($attributes);
    }

    public function findReportOf(int $ratingId, string $reporterType, int $reporterId): ?RatingReport
    {
        return RatingReport::query()->where('rating_id', $ratingId)->where('reporter_type', $reporterType)->where('reporter_id', $reporterId)->first();
    }

    public function findReportByUuid(string $uuid): ?RatingReport
    {
        return RatingReport::query()->where('uuid', $uuid)->first();
    }

    public function lockReport(int $id): ?RatingReport
    {
        return RatingReport::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function updateReport(RatingReport $report, array $attributes): RatingReport
    {
        $report->forceFill($attributes)->save();

        return $report;
    }

    public function reportsOf(int $ratingId): Collection
    {
        return RatingReport::query()->with('handledBy:id,name')->where('rating_id', $ratingId)->orderByDesc('id')->get();
    }

    public function settleOpenReports(int $ratingId, string $status, int $adminId, string $note): int
    {
        return RatingReport::query()->where('rating_id', $ratingId)->where('status', RatingReportStatus::Open->value)->update([
            'status' => $status, 'handled_by_admin_id' => $adminId, 'handled_at' => now(), 'resolution_note' => $note, 'updated_at' => now(),
        ]);
    }

    public function createAction(array $attributes): ModerationAction
    {
        return ModerationAction::query()->create($attributes);
    }

    public function actionsOf(int $ratingId): Collection
    {
        return ModerationAction::query()->with('admin:id,name')->where('rating_id', $ratingId)->orderByDesc('id')->get();
    }

    public function createAppeal(array $attributes): RatingAppeal
    {
        return RatingAppeal::query()->create($attributes);
    }

    public function findAppealOf(int $ratingId, string $appellantType, int $appellantId): ?RatingAppeal
    {
        return RatingAppeal::query()->where('rating_id', $ratingId)->where('appellant_type', $appellantType)->where('appellant_id', $appellantId)->first();
    }

    public function findAppealByUuid(string $uuid): ?RatingAppeal
    {
        return RatingAppeal::query()->where('uuid', $uuid)->first();
    }

    public function lockAppeal(int $id): ?RatingAppeal
    {
        return RatingAppeal::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function updateAppeal(RatingAppeal $appeal, array $attributes): RatingAppeal
    {
        $appeal->forceFill($attributes)->save();

        return $appeal;
    }

    public function appealsOf(int $ratingId): Collection
    {
        return RatingAppeal::query()->with('decidedBy:id,name')->where('rating_id', $ratingId)->orderByDesc('id')->get();
    }

    public function countOpenAppeals(): int
    {
        return RatingAppeal::query()->where('status', 'open')->count();
    }

    public function countOpenReports(): int
    {
        return RatingReport::query()->where('status', RatingReportStatus::Open->value)->count();
    }

    public function reportsDatatableQuery(ModerationQueueFilterData $filters): Builder
    {
        return RatingReport::query()
            ->join('booking_ratings', 'booking_ratings.id', '=', 'rating_reports.rating_id')
            ->join('bookings', 'bookings.id', '=', 'booking_ratings.booking_id')
            ->select('rating_reports.*')
            ->selectRaw('booking_ratings.uuid as rating_uuid, booking_ratings.stars as rating_stars, bookings.code as booking_code')
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('rating_reports.status', $filters->statuses))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('rating_reports.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('rating_reports.created_at', '<=', $filters->to?->endOfDay()));
    }

    public function appealsDatatableQuery(ModerationQueueFilterData $filters): Builder
    {
        return RatingAppeal::query()
            ->join('booking_ratings', 'booking_ratings.id', '=', 'rating_appeals.rating_id')
            ->join('bookings', 'bookings.id', '=', 'booking_ratings.booking_id')
            ->select('rating_appeals.*')
            ->selectRaw('booking_ratings.uuid as rating_uuid, booking_ratings.stars as rating_stars, bookings.code as booking_code')
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('rating_appeals.status', $filters->statuses))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('rating_appeals.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('rating_appeals.created_at', '<=', $filters->to?->endOfDay()));
    }

    public function scrubOfUser(int $userId): array
    {
        $reports = RatingReport::query()->where('reporter_user_id', $userId)->whereNotNull('details')->update(['details' => null]);
        $appeals = RatingAppeal::query()->where('appellant_user_id', $userId)->where('message', '!=', self::SCRUBBED_TEXT)->update(['message' => self::SCRUBBED_TEXT]);

        return ['reports' => $reports, 'appeals' => $appeals];
    }

    public function reportsOfUser(int $userId): Collection
    {
        return RatingReport::query()->where('reporter_user_id', $userId)->orderBy('id')->get();
    }

    public function appealsOfUser(int $userId): Collection
    {
        return RatingAppeal::query()->where('appellant_user_id', $userId)->orderBy('id')->get();
    }

    public function userOfParty(string $partyType, int $partyId): ?User
    {
        if ($partyType === ActorType::Provider->value) {
            return ProviderProfile::query()->with('user')->find($partyId)?->user;
        }

        return User::query()->withTrashed()->find($partyId);
    }

    public function partyName(string $partyType, int $partyId): ?string
    {
        $name = $this->userOfParty($partyType, $partyId)?->name;

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function partyIdOfUser(User $user, string $app): ?int
    {
        if ($app === ActorType::Provider->value) {
            $profile = ProviderProfile::query()->where('user_id', $user->id)->first();

            return $profile?->id;
        }

        return $user->id;
    }
}
