<?php

namespace App\Repositories\Contracts\Provider;

use App\DTOs\Provider\ProviderFilterData;
use App\DTOs\Provider\VerificationQueueFilterData;
use App\Enums\Location\OfflineReason;
use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Provider profiles (applications and working profiles). Statuses are written only through `applyStatus`, called by
 * `ProviderStatusService`.
 *
 * @extends BaseRepositoryInterface<ProviderProfile>
 */
interface ProviderProfileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<ProviderProfile>
     */
    public function datatableQuery(ProviderFilterData $filters): Builder;

    /**
     * @return Builder<ProviderProfile>
     */
    public function verificationQueueQuery(VerificationQueueFilterData $filters): Builder;

    public function findByUserId(int $userId): ?ProviderProfile;

    public function findByUuidWithRelations(string $uuid): ?ProviderProfile;

    /**
     * Row lock (inside a transaction).
     */
    public function lockForUpdate(int $id): ?ProviderProfile;

    public function createFor(User $user): ProviderProfile;

    /**
     * Writes the editable application fields (type, city, date of birth) and bumps the lock version.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateApplication(ProviderProfile $profile, array $attributes): ProviderProfile;

    /**
     * Writes the status with its reason and optional end of a temporary state; sets the change timestamp and the
     * submitted/approved markers that belong to the new status.
     */
    public function applyStatus(ProviderProfile $profile, ProviderStatus $status, ?string $reason, ?CarbonInterface $expiresAt): ProviderProfile;

    public function setOnline(ProviderProfile $profile, bool $online): ProviderProfile;

    /**
     * Marks the provider offline and remembers why (shown in the app and to support).
     */
    public function setOffline(ProviderProfile $profile, OfflineReason $reason): ProviderProfile;

    /**
     * Online providers whose newest position is older than the cut-off (or that never reported one since going online).
     *
     * @return Collection<int, ProviderProfile>
     */
    public function onlineWithLocationOlderThan(CarbonInterface $before, int $limit): Collection;

    /**
     * Profiles with their full account row (status checks), for dispatch filtering.
     *
     * @param list<int> $ids
     * @return Collection<int, ProviderProfile>
     */
    public function findManyForDispatch(array $ids): Collection;

    /**
     * A trip finished: one more completed job on the provider's public record.
     */
    public function incrementCompletedJobs(int $providerProfileId): void;

    public function setCurrentVehicle(ProviderProfile $profile, ?int $vehicleId): ProviderProfile;

    public function assignTo(ProviderProfile $profile, ?int $adminId): ProviderProfile;

    /**
     * Clears personal data kept on the profile when the account is anonymised (birth date, city, reason).
     */
    public function scrubPersonalData(ProviderProfile $profile): ProviderProfile;

    /**
     * @return Collection<int, ProviderProfile> temporarily suspended providers whose suspension ended at or before $now
     */
    public function suspensionsEndedBefore(CarbonInterface $now, int $limit): Collection;

    /**
     * @return Collection<int, ProviderProfile>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, ProviderProfile>
     */
    public function findManyByIds(array $ids): Collection;
}
