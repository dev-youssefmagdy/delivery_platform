<?php

namespace App\Repositories\Eloquent\Coupon;

use App\DTOs\Coupon\CouponFilterData;
use App\Enums\Coupon\CouponScopeType;
use App\Enums\Coupon\CouponStatus;
use App\Models\Coupon;
use App\Models\CouponScope;
use App\Models\CouponVersion;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Coupon>
 */
final class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(CouponFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->code !== null, static fn (Builder $query) => $query->where('code', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_strtoupper((string) $filters->code)).'%'))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (CouponStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->discountTypes !== [], static fn (Builder $query) => $query->whereIn('discount_type', array_map(static fn ($type): string => $type->value, $filters->discountTypes)))
            ->when($filters->fundingSources !== [], static fn (Builder $query) => $query->whereIn('funding_source', array_map(static fn ($source): string => $source->value, $filters->fundingSources)))
            ->when($filters->familyIds !== [], static fn (Builder $query) => $query->whereIn('id', static fn ($sub) => $sub
                ->select('coupon_id')->from('coupon_scopes')->where('type', CouponScopeType::ServiceFamily->value)->whereIn('ref_id', $filters->familyIds)))
            ->when($filters->activeOn !== null, static fn (Builder $query) => $query
                ->where(static fn (Builder $inner) => $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $filters->activeOn?->endOfDay()))
                ->where(static fn (Builder $inner) => $inner->whereNull('ends_at')->orWhere('ends_at', '>', $filters->activeOn?->startOfDay()))
                ->whereIn('status', [CouponStatus::Active->value, CouponStatus::Scheduled->value, CouponStatus::Paused->value, CouponStatus::Exhausted->value]))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $filters->to?->endOfDay())))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner->whereNull('ends_at')->orWhere('ends_at', '>', $filters->from?->startOfDay())));
    }

    public function findByCode(string $code): ?Coupon
    {
        return $this->query()->with('scopes')->where('code', mb_strtoupper(trim($code)))->first();
    }

    public function findByUuid(string $uuid): ?Coupon
    {
        return $this->query()->where('uuid', $uuid)->first();
    }

    public function activeAutomatic(CarbonInterface $at): Collection
    {
        return $this->query()->with('scopes')
            ->whereNull('code')
            ->where('status', CouponStatus::Active->value)
            ->where(static fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(static fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->orderByDesc('priority')->orderBy('id')
            ->get();
    }

    public function dueToStart(CarbonInterface $at): Collection
    {
        return $this->query()->where('status', CouponStatus::Scheduled->value)
            ->where(static fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(static fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->get();
    }

    public function dueToExpire(CarbonInterface $at): Collection
    {
        return $this->query()
            ->whereIn('status', [CouponStatus::Scheduled->value, CouponStatus::Active->value, CouponStatus::Paused->value, CouponStatus::Exhausted->value])
            ->whereNotNull('ends_at')->where('ends_at', '<=', $at)
            ->get();
    }

    public function consumed(): Collection
    {
        return $this->query()->where('status', CouponStatus::Active->value)
            ->where(static fn (Builder $query) => $query
                ->where(static fn (Builder $inner) => $inner->whereNotNull('global_limit')->whereColumn('used_count', '>=', 'global_limit'))
                ->orWhere(static fn (Builder $inner) => $inner->whereNotNull('budget_minor')->whereColumn('spent_minor', '>=', 'budget_minor')))
            ->get();
    }

    public function withCapacityAgain(): Collection
    {
        return $this->query()->where('status', CouponStatus::Exhausted->value)
            ->where(static fn (Builder $query) => $query->whereNull('global_limit')->orWhereColumn('used_count', '<', 'global_limit'))
            ->where(static fn (Builder $query) => $query->whereNull('budget_minor')->orWhereColumn('spent_minor', '<', 'budget_minor'))
            ->get();
    }

    public function syncScopes(Coupon $coupon, array $scopes): void
    {
        CouponScope::query()->where('coupon_id', $coupon->id)->delete();
        $rows = [];

        foreach ($scopes as $type => $ids) {
            if (CouponScopeType::tryFrom($type) === null) {
                continue;
            }

            foreach (array_values(array_unique($ids)) as $id) {
                $rows[] = ['coupon_id' => $coupon->id, 'type' => $type, 'ref_id' => $id];
            }
        }

        if ($rows !== []) {
            CouponScope::query()->insert($rows);
        }

        $coupon->unsetRelation('scopes');
    }

    public function addVersion(Coupon $coupon, array $attributes): CouponVersion
    {
        return CouponVersion::query()->create($attributes + ['coupon_id' => $coupon->id]);
    }

    public function version(int $versionId): ?CouponVersion
    {
        return CouponVersion::query()->find($versionId);
    }

    public function currentVersion(Coupon $coupon): ?CouponVersion
    {
        return CouponVersion::query()->where('coupon_id', $coupon->id)->where('version', $coupon->version)->first();
    }

    public function adjustCounters(Coupon $coupon, int $usedCount = 0, int $reservedCount = 0, int $spentMinor = 0, int $reservedMinor = 0): void
    {
        $coupon->forceFill([
            'used_count' => max(0, $coupon->used_count + $usedCount),
            'reserved_count' => max(0, $coupon->reserved_count + $reservedCount),
            'spent_minor' => max(0, $coupon->spent_minor + $spentMinor),
            'reserved_minor' => max(0, $coupon->reserved_minor + $reservedMinor),
        ])->save();
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        return DB::table('coupons')->where('code', mb_strtoupper(trim($code)))->when($exceptId !== null, static fn ($query) => $query->where('id', '!=', $exceptId))->exists();
    }
}
