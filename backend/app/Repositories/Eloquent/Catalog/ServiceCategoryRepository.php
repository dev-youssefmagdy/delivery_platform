<?php

namespace App\Repositories\Eloquent\Catalog;

use App\DTOs\Catalog\ServiceCategoryFilterData;
use App\Enums\Catalog\BookingType;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ServiceCategory>
 */
final class ServiceCategoryRepository extends BaseRepository implements ServiceCategoryRepositoryInterface
{
    public function __construct(ServiceCategory $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ServiceCategoryFilterData $filters): Builder
    {
        return $this->query()
            ->with('family:id,name,code')
            ->with('publishedVersion:id,version')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->familyId, static fn (Builder $query, int $id) => $query->where('service_family_id', $id))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active))
            ->when($filters->bookingType, static fn (Builder $query, BookingType $type) => $query->whereJsonContains('allowed_booking_types', $type->value))
            ->when($filters->negotiationEnabled !== null, static fn (Builder $query) => $query->where('negotiation_enabled', $filters->negotiationEnabled));
    }

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->when($term !== '', static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('code', 'like', $like)
                ->orWhere('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like)))
            ->orderBy('sort_order')->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->orderBy('sort_order')->get();
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('code', $code)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function activeCount(?int $exceptId = null, ?int $exceptFamilyId = null): int
    {
        return $this->query()
            ->where('active', true)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->when($exceptFamilyId !== null, static fn (Builder $query) => $query->where('service_family_id', '!=', $exceptFamilyId))
            ->count();
    }

    public function syncVehicleCategories(ServiceCategory $category, array $vehicleCategoryIds): array
    {
        /** @var array{attached: list<int>, detached: list<int>, updated: list<int>} $changes */
        $changes = $category->vehicleCategories()->sync($vehicleCategoryIds);

        return ['attached' => array_map('intval', $changes['attached']), 'detached' => array_map('intval', $changes['detached'])];
    }

    public function vehicleCategoryIds(ServiceCategory $category): array
    {
        return $category->vehicleCategories()->orderBy('vehicle_categories.sort_order')->pluck('vehicle_categories.id')->map(static fn ($id): int => (int) $id)->all();
    }

    public function loadDetails(ServiceCategory $category): ServiceCategory
    {
        return $category->load(['family', 'vehicleCategories', 'publishedVersion']);
    }

    public function allOrdered(): Collection
    {
        return $this->query()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function publicList(?BookingType $bookingType, ?int $familyId, ?int $zoneId = null): Collection
    {
        return $this->query()
            ->where('active', true)
            ->whereNotNull('published_version_id')
            ->whereHas('family', static fn (Builder $query) => $query->where('active', true))
            ->with(['family', 'publishedVersion.formSchema'])
            ->when($bookingType, static fn (Builder $query, BookingType $type) => $query->whereJsonContains('allowed_booking_types', $type->value))
            ->when($familyId, static fn (Builder $query, int $id) => $query->where('service_family_id', $id))
            ->when($zoneId !== null, static fn (Builder $query) => $query->whereIn('id', static fn ($links) => $links
                ->select('service_category_id')->from('zone_service_category')->where('service_zone_id', $zoneId)->where('active', true)))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }
}
