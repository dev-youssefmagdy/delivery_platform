<?php

namespace App\Repositories\Eloquent\Provider;

use App\Enums\Provider\ServiceApprovalStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ProviderServiceRepository implements ProviderServiceRepositoryInterface
{
    public function forProvider(ProviderProfile $profile): Collection
    {
        return ProviderServiceCategory::query()->with('serviceCategory')->where('provider_profile_id', $profile->id)->orderBy('id')->get();
    }

    public function datatableQuery(ProviderProfile $profile): Builder
    {
        return ProviderServiceCategory::query()->with('serviceCategory')->where('provider_profile_id', $profile->id);
    }

    public function find(ProviderProfile $profile, int $serviceCategoryId): ?ProviderServiceCategory
    {
        return ProviderServiceCategory::query()
            ->where('provider_profile_id', $profile->id)
            ->where('service_category_id', $serviceCategoryId)
            ->first();
    }

    public function request(ProviderProfile $profile, int $serviceCategoryId): ProviderServiceCategory
    {
        return ProviderServiceCategory::query()->create([
            'provider_profile_id' => $profile->id,
            'service_category_id' => $serviceCategoryId,
            'status' => ServiceApprovalStatus::Requested,
        ]);
    }

    public function remove(ProviderProfile $profile, int $serviceCategoryId): void
    {
        ProviderServiceCategory::query()
            ->where('provider_profile_id', $profile->id)
            ->where('service_category_id', $serviceCategoryId)
            ->delete();
    }

    public function decide(ProviderServiceCategory $service, ServiceApprovalStatus $status, ?int $adminId, ?string $reason): ProviderServiceCategory
    {
        $service->forceFill([
            'status' => $status,
            'decided_by_admin_id' => $adminId,
            'decided_at' => now(),
            'decision_reason' => $reason,
        ])->save();

        return $service;
    }

    public function categoryIds(ProviderProfile $profile, array $statuses): array
    {
        return ProviderServiceCategory::query()
            ->where('provider_profile_id', $profile->id)
            ->whereIn('status', array_map(static fn (ServiceApprovalStatus $status): string => $status->value, $statuses))
            ->pluck('service_category_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
