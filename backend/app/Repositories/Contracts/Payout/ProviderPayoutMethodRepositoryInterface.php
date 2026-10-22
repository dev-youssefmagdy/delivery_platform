<?php

namespace App\Repositories\Contracts\Payout;

use App\Models\ProviderPayoutMethod;
use Illuminate\Support\Collection;

/**
 * Payout methods of providers (details encrypted by the model cast).
 */
interface ProviderPayoutMethodRepositoryInterface
{
    public function find(int $id): ?ProviderPayoutMethod;

    public function findForProvider(int $providerId, string $uuid): ?ProviderPayoutMethod;

    public function findByUuid(string $uuid): ?ProviderPayoutMethod;

    /**
     * Locks the provider's methods rows FOR UPDATE (default switching).
     *
     * @return Collection<int, ProviderPayoutMethod>
     */
    public function lockForProvider(int $providerId): Collection;

    /**
     * @return Collection<int, ProviderPayoutMethod>
     */
    public function forProvider(int $providerId): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): ProviderPayoutMethod;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(ProviderPayoutMethod $method, array $attributes): ProviderPayoutMethod;

    public function delete(ProviderPayoutMethod $method): void;

    public function clearDefault(int $providerId): void;

    /**
     * Soft-deleted methods of a provider, for the anonymisation scrub.
     */
    public function purgeForProvider(int $providerId): int;
}
