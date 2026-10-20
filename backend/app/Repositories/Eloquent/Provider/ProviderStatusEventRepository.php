<?php

namespace App\Repositories\Eloquent\Provider;

use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderStatusEvent;
use App\Repositories\Contracts\Provider\ProviderStatusEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProviderStatusEventRepository implements ProviderStatusEventRepositoryInterface
{
    public function append(ProviderProfile $profile, ?ProviderStatus $from, ProviderStatus $to, string $actorType, ?int $actorId, ?string $reason, array $meta = []): ProviderStatusEvent
    {
        return ProviderStatusEvent::query()->create([
            'provider_profile_id' => $profile->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'reason' => $reason,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    public function datatableQuery(ProviderProfile $profile): Builder
    {
        return ProviderStatusEvent::query()->with('admin:id,name')->where('provider_profile_id', $profile->id);
    }

    public function latestInto(ProviderProfile $profile, ProviderStatus $status): ?ProviderStatusEvent
    {
        return ProviderStatusEvent::query()
            ->where('provider_profile_id', $profile->id)
            ->where('to_status', $status->value)
            ->orderByDesc('id')
            ->first();
    }

    public function latest(ProviderProfile $profile, int $limit): Collection
    {
        return ProviderStatusEvent::query()->where('provider_profile_id', $profile->id)->orderByDesc('id')->limit($limit)->get();
    }
}
