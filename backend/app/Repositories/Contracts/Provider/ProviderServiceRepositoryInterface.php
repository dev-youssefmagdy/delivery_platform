<?php

namespace App\Repositories\Contracts\Provider;

use App\Enums\Provider\ServiceApprovalStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The service categories a provider asked to serve (`provider_service_category`).
 */
interface ProviderServiceRepositoryInterface
{
    /**
     * @return Collection<int, ProviderServiceCategory>
     */
    public function forProvider(ProviderProfile $profile): Collection;

    /**
     * @return Builder<ProviderServiceCategory>
     */
    public function datatableQuery(ProviderProfile $profile): Builder;

    public function find(ProviderProfile $profile, int $serviceCategoryId): ?ProviderServiceCategory;

    public function request(ProviderProfile $profile, int $serviceCategoryId): ProviderServiceCategory;

    public function remove(ProviderProfile $profile, int $serviceCategoryId): void;

    public function decide(ProviderServiceCategory $service, ServiceApprovalStatus $status, ?int $adminId, ?string $reason): ProviderServiceCategory;

    /**
     * Ids of categories in the given statuses.
     *
     * @param list<ServiceApprovalStatus> $statuses
     * @return list<int>
     */
    public function categoryIds(ProviderProfile $profile, array $statuses): array;
}
