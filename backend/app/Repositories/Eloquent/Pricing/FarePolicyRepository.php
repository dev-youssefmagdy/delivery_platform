<?php

namespace App\Repositories\Eloquent\Pricing;

use App\DTOs\Pricing\FarePolicyFilterData;
use App\Models\FarePolicy;
use App\Repositories\Contracts\Pricing\FarePolicyRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<FarePolicy>
 */
final class FarePolicyRepository extends BaseRepository implements FarePolicyRepositoryInterface
{
    public function __construct(FarePolicy $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(FarePolicyFilterData $filters): Builder
    {
        return $this->query()
            ->with(['serviceCategory:id,code,name', 'serviceZone:id,name'])
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->categoryIds))
            ->when($filters->zoneIds !== [], static fn (Builder $query) => $query->whereIn('service_zone_id', $filters->zoneIds))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active))
            ->when($filters->effectiveOn !== null, static fn (Builder $query) => $query
                ->whereDate('effective_from', '<=', $filters->effectiveOn?->toDateString())
                ->where(static fn (Builder $inner) => $inner->whereNull('effective_to')->orWhereDate('effective_to', '>', $filters->effectiveOn?->toDateString())));
    }

    public function effectiveFor(int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $at): ?FarePolicy
    {
        $candidates = $this->query()
            ->where('service_category_id', $serviceCategoryId)
            ->where('active', true)
            ->where('effective_from', '<=', $at)
            ->where(static fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->where(static fn (Builder $query) => $query->whereNull('service_zone_id')->when($serviceZoneId !== null, static fn (Builder $inner) => $inner->orWhere('service_zone_id', $serviceZoneId)))
            ->orderByRaw('service_zone_id is null')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        return $candidates->first();
    }

    public function overlapping(int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $from, ?CarbonInterface $to, ?int $exceptId = null): array
    {
        return $this->query()
            ->where('service_category_id', $serviceCategoryId)
            ->where('active', true)
            ->when($serviceZoneId === null, static fn (Builder $query) => $query->whereNull('service_zone_id'), static fn (Builder $query) => $query->where('service_zone_id', $serviceZoneId))
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->when($to !== null, static fn (Builder $query) => $query->where('effective_from', '<', $to))
            ->where(static fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $from))
            ->get()
            ->all();
    }

    public function bookingCount(FarePolicy $policy): int
    {
        return (int) DB::table('bookings')->where('fare_policy_id', $policy->id)->count();
    }
}
