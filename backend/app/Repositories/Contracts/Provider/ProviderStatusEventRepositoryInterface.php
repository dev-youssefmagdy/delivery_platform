<?php

namespace App\Repositories\Contracts\Provider;

use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderStatusEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ProviderStatusEventRepositoryInterface
{
    /**
     * @param array<string, mixed> $meta
     */
    public function append(ProviderProfile $profile, ?ProviderStatus $from, ProviderStatus $to, string $actorType, ?int $actorId, ?string $reason, array $meta = []): ProviderStatusEvent;

    /**
     * @return Builder<ProviderStatusEvent>
     */
    public function datatableQuery(ProviderProfile $profile): Builder;

    /**
     * Latest event that moved the provider INTO the given status.
     */
    public function latestInto(ProviderProfile $profile, ProviderStatus $status): ?ProviderStatusEvent;

    /**
     * @return Collection<int, ProviderStatusEvent>
     */
    public function latest(ProviderProfile $profile, int $limit): Collection;
}
