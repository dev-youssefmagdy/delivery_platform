<?php

namespace App\Repositories\Contracts\Safety;

use App\Models\TrustedContact;
use App\Models\TrustedContactAction;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Trusted contacts of a person and the records of what the platform did towards them.
 *
 * @extends BaseRepositoryInterface<TrustedContact>
 */
interface TrustedContactRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, TrustedContact> oldest first
     */
    public function forUser(int $userId): Collection;

    public function findOfUser(string $uuid, int $userId): ?TrustedContact;

    public function countOfUser(int $userId): int;

    public function phoneExists(int $userId, string $phoneHash, ?int $exceptId = null): bool;

    /**
     * @param array<string, mixed> $attributes
     */
    public function recordAction(array $attributes): TrustedContactAction;

    /**
     * @return Collection<int, TrustedContactAction> oldest first
     */
    public function actionsForIncident(int $incidentId): Collection;

    /**
     * Trip links sent for a booking by a person since `$since` (hourly limit).
     */
    public function tripSharesSince(int $userId, int $bookingId, CarbonInterface $since): int;

    /**
     * @return Collection<int, TrustedContactAction> everything done towards the person's contacts (personal-data export)
     */
    public function actionsOfUser(int $userId): Collection;

    /**
     * Account deletion: contacts are deleted; action records keep time, type and outcome and lose the contact's name and masked phone.
     *
     * @return array{contacts: int, actions: int}
     */
    public function purgeForUser(int $userId): array;
}
