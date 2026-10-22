<?php

namespace App\Repositories\Eloquent\Payout;

use App\Models\ProviderPayoutMethod;
use App\Repositories\Contracts\Payout\ProviderPayoutMethodRepositoryInterface;
use Illuminate\Support\Collection;

final class ProviderPayoutMethodRepository implements ProviderPayoutMethodRepositoryInterface
{
    public function find(int $id): ?ProviderPayoutMethod
    {
        return ProviderPayoutMethod::query()->find($id);
    }

    public function findForProvider(int $providerId, string $uuid): ?ProviderPayoutMethod
    {
        return ProviderPayoutMethod::query()->where('provider_profile_id', $providerId)->where('uuid', $uuid)->first();
    }

    public function findByUuid(string $uuid): ?ProviderPayoutMethod
    {
        return ProviderPayoutMethod::query()->where('uuid', $uuid)->first();
    }

    public function lockForProvider(int $providerId): Collection
    {
        return ProviderPayoutMethod::query()->where('provider_profile_id', $providerId)->orderBy('id')->lockForUpdate()->get();
    }

    public function forProvider(int $providerId): Collection
    {
        return ProviderPayoutMethod::query()->where('provider_profile_id', $providerId)->orderByDesc('is_default')->orderBy('id')->get();
    }

    public function insert(array $attributes): ProviderPayoutMethod
    {
        $method = new ProviderPayoutMethod;
        $method->forceFill($attributes)->save();

        return $method;
    }

    public function update(ProviderPayoutMethod $method, array $attributes): ProviderPayoutMethod
    {
        $method->forceFill($attributes)->save();

        return $method;
    }

    public function delete(ProviderPayoutMethod $method): void
    {
        $method->delete();
    }

    public function clearDefault(int $providerId): void
    {
        ProviderPayoutMethod::query()->where('provider_profile_id', $providerId)->where('is_default', true)->update(['is_default' => false]);
    }

    public function purgeForProvider(int $providerId): int
    {
        $count = 0;

        foreach (ProviderPayoutMethod::withTrashed()->where('provider_profile_id', $providerId)->get() as $method) {
            // The history keeps a masked snapshot on each payout; the encrypted details and the label go.
            $method->forceFill(['details' => [], 'label' => '—', 'last4' => null, 'is_default' => false, 'verified_at' => null])->save();

            if (! $method->trashed()) {
                $method->delete();
            }

            $count++;
        }

        return $count;
    }
}
