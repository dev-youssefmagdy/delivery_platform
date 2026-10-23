<?php

namespace App\Repositories\Contracts\Safety;

use App\DTOs\Safety\IncidentFilterData;
use App\Models\SafetyIncident;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Every query on `safety_incidents`.
 *
 * @extends BaseRepositoryInterface<SafetyIncident>
 */
interface SafetyIncidentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Inserts the case and sets its human number (INC-000123) in the same call.
     *
     * @param array<string, mixed> $attributes
     */
    public function createNumbered(array $attributes): SafetyIncident;

    public function findOfReporter(string $uuid, int $userId): ?SafetyIncident;

    /**
     * @return Builder<SafetyIncident> the person's own cases, newest first
     */
    public function userQuery(int $userId): Builder;

    /**
     * The still-active SOS the person raised on this booking (a repeated press joins it instead of opening a second case).
     */
    public function activeSosFor(int $bookingId, int $userId): ?SafetyIncident;

    /**
     * Non-urgent reports of the person at or after `$since` (the hourly limit).
     */
    public function countReportsSince(int $userId, CarbonInterface $since): int;

    /**
     * @return Builder<SafetyIncident>
     */
    public function datatableQuery(IncidentFilterData $filters): Builder;

    /**
     * @return Collection<int, SafetyIncident> every case linked to a booking, newest first
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * @return Collection<int, SafetyIncident> cases a person raised (personal-data export)
     */
    public function allOfUser(int $userId): Collection;

    /**
     * @return Collection<int, SafetyIncident> new cases whose acknowledgement is overdue and not yet flagged
     */
    public function slaOverdue(CarbonInterface $now, int $limit): Collection;

    /**
     * @return Collection<int, SafetyIncident> resolved cases past retention without a legal hold
     */
    public function expired(CarbonInterface $resolvedBefore, int $limit): Collection;

    public function countActive(): int;

    public function countUrgentNew(): int;

    /**
     * Account deletion (legal hold wins): for the person's cases WITHOUT a hold, the narrative, callback phone, location, the
     * person's own messages and every file go; the rows, categories, times and staff actions stay. Held cases are left whole.
     *
     * @return array{scrubbed: int, held: int, files: list<string>}
     */
    public function scrubOfUser(int $userId): array;
}
