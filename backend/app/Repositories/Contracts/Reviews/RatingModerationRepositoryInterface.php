<?php

namespace App\Repositories\Contracts\Reviews;

use App\DTOs\Reviews\ModerationQueueFilterData;
use App\Models\ModerationAction;
use App\Models\RatingAppeal;
use App\Models\RatingReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reports, moderation actions and appeals of ratings (P5.2). Rows only; the rules live in the services.
 */
interface RatingModerationRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function createReport(array $attributes): RatingReport;

    public function findReportOf(int $ratingId, string $reporterType, int $reporterId): ?RatingReport;

    public function findReportByUuid(string $uuid): ?RatingReport;

    public function lockReport(int $id): ?RatingReport;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateReport(RatingReport $report, array $attributes): RatingReport;

    /**
     * @return Collection<int, RatingReport> newest first
     */
    public function reportsOf(int $ratingId): Collection;

    /**
     * Closes every open report of the rating with the given status (a moderation decision settles them together).
     */
    public function settleOpenReports(int $ratingId, string $status, int $adminId, string $note): int;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createAction(array $attributes): ModerationAction;

    /**
     * @return Collection<int, ModerationAction> newest first, with the admin
     */
    public function actionsOf(int $ratingId): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createAppeal(array $attributes): RatingAppeal;

    public function findAppealOf(int $ratingId, string $appellantType, int $appellantId): ?RatingAppeal;

    public function findAppealByUuid(string $uuid): ?RatingAppeal;

    public function lockAppeal(int $id): ?RatingAppeal;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateAppeal(RatingAppeal $appeal, array $attributes): RatingAppeal;

    /**
     * @return Collection<int, RatingAppeal> newest first
     */
    public function appealsOf(int $ratingId): Collection;

    public function countOpenAppeals(): int;

    public function countOpenReports(): int;

    /**
     * @return Builder<RatingReport>
     */
    public function reportsDatatableQuery(ModerationQueueFilterData $filters): Builder;

    /**
     * @return Builder<RatingAppeal>
     */
    public function appealsDatatableQuery(ModerationQueueFilterData $filters): Builder;

    /**
     * Account deletion: removes the free text the person wrote in reports and appeals (the rows and decisions stay for the audit trail).
     *
     * @return array{reports: int, appeals: int}
     */
    public function scrubOfUser(int $userId): array;

    /**
     * @return Collection<int, RatingReport>
     */
    public function reportsOfUser(int $userId): Collection;

    /**
     * @return Collection<int, RatingAppeal>
     */
    public function appealsOfUser(int $userId): Collection;
}
