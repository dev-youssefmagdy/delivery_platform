<?php

namespace App\Repositories\Contracts\Dispatch;

use App\Enums\Dispatch\DispatchResponse;
use App\Models\DispatchRecipient;
use Illuminate\Support\Collection;

interface DispatchRecipientRepositoryInterface
{
    /**
     * Providers that were already notified of the request in any wave.
     *
     * @return list<int>
     */
    public function providerIdsFor(int $bookingId): array;

    /**
     * Records the recipients of a wave; a provider that is already a recipient is left untouched.
     *
     * @param list<array{provider_profile_id: int, distance_m: int}> $rows
     * @return list<int> provider ids that were newly recorded
     */
    public function recordWave(int $bookingId, int $wave, array $rows): array;

    public function find(int $bookingId, int $providerProfileId): ?DispatchRecipient;

    public function markSeen(int $bookingId, int $providerProfileId): void;

    public function respond(int $bookingId, int $providerProfileId, DispatchResponse $response): void;

    /**
     * @return Collection<int, DispatchRecipient>
     */
    public function forBooking(int $bookingId): Collection;

    public function countForWave(int $bookingId, int $wave): int;
}
