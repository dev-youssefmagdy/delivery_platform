<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Booking\CancellationPolicyFilterData;
use App\Models\CancellationPolicy;
use App\Repositories\Contracts\Booking\CancellationPolicyRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<CancellationPolicy>
 */
final class CancellationPolicyRepository extends BaseRepository implements CancellationPolicyRepositoryInterface
{
    public function __construct(CancellationPolicy $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(CancellationPolicyFilterData $filters): Builder
    {
        return $this->query()
            ->with(['serviceCategory:id,code,name', 'serviceZone:id,name'])
            ->when($filters->name !== null, static fn (Builder $query) => $query->where('name', 'like', '%'.$filters->name.'%'))
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->categoryIds))
            ->when($filters->zoneIds !== [], static fn (Builder $query) => $query->whereIn('service_zone_id', $filters->zoneIds))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active))
            ->when($filters->effectiveTo !== null, static fn (Builder $query) => $query->where('effective_from', '<=', $filters->effectiveTo?->endOfDay()))
            ->when($filters->effectiveFrom !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->whereNull('effective_to')
                ->orWhere('effective_to', '>', $filters->effectiveFrom?->startOfDay())));
    }

    public function effectiveFor(int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $at): ?CancellationPolicy
    {
        return $this->query()
            ->where('active', true)
            ->where('effective_from', '<=', $at)
            ->where(static fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->where(static fn (Builder $query) => $query->whereNull('service_category_id')->orWhere('service_category_id', $serviceCategoryId))
            ->where(static fn (Builder $query) => $query->whereNull('service_zone_id')->when($serviceZoneId !== null, static fn (Builder $inner) => $inner->orWhere('service_zone_id', $serviceZoneId)))
            ->orderByRaw('service_zone_id is null')
            ->orderByRaw('service_category_id is null')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function overlapping(?int $serviceCategoryId, ?int $serviceZoneId, CarbonInterface $from, ?CarbonInterface $to, ?int $exceptId = null): array
    {
        return $this->query()
            ->where('active', true)
            ->when($serviceCategoryId === null, static fn (Builder $query) => $query->whereNull('service_category_id'), static fn (Builder $query) => $query->where('service_category_id', $serviceCategoryId))
            ->when($serviceZoneId === null, static fn (Builder $query) => $query->whereNull('service_zone_id'), static fn (Builder $query) => $query->where('service_zone_id', $serviceZoneId))
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->when($to !== null, static fn (Builder $query) => $query->where('effective_from', '<', $to))
            ->where(static fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $from))
            ->get()
            ->all();
    }

    public function bookingCount(CancellationPolicy $policy): int
    {
        return (int) DB::table('bookings')->where('cancellation_policy_id', $policy->id)->count();
    }
}
