<?php

namespace App\Repositories\Eloquent\Finance;

use App\DTOs\Finance\CommissionContext;
use App\DTOs\Finance\CommissionRuleFilterData;
use App\Enums\Finance\CommissionType;
use App\Models\CommissionRule;
use App\Models\CommissionRuleVersion;
use App\Repositories\Contracts\Finance\CommissionRuleRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<CommissionRule>
 */
final class CommissionRuleRepository extends BaseRepository implements CommissionRuleRepositoryInterface
{
    public function __construct(CommissionRule $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(CommissionRuleFilterData $filters): Builder
    {
        return $this->query()
            ->with(['serviceCategory:id,code,name', 'serviceZone:id,name', 'currentVersion'])
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->categoryIds))
            ->when($filters->zoneIds !== [], static fn (Builder $query) => $query->whereIn('service_zone_id', $filters->zoneIds))
            ->when($filters->types !== [], static fn (Builder $query) => $query->whereIn('current_version_id', static fn ($sub) => $sub
                ->select('id')->from('commission_rule_versions')->whereIn('type', array_map(static fn (CommissionType $type): string => $type->value, $filters->types))))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active))
            ->when($filters->effectiveTo !== null, static fn (Builder $query) => $query->where('effective_from', '<=', $filters->effectiveTo?->endOfDay()))
            ->when($filters->effectiveFrom !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->whereNull('effective_to')
                ->orWhere('effective_to', '>', $filters->effectiveFrom?->startOfDay())));
    }

    public function candidatesFor(CommissionContext $context): Collection
    {
        return $this->query()
            ->with('currentVersion')
            ->where('active', true)
            ->where('currency', $context->currency)
            ->whereNotNull('current_version_id')
            ->where('effective_from', '<=', $context->at)
            ->where(static fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $context->at))
            ->where(static fn (Builder $query) => $query->whereNull('service_category_id')->orWhere('service_category_id', $context->serviceCategoryId))
            ->where(static fn (Builder $query) => $query->whereNull('service_zone_id')->when($context->serviceZoneId !== null, static fn (Builder $inner) => $inner->orWhere('service_zone_id', $context->serviceZoneId)))
            ->where(static fn (Builder $query) => $query->whereNull('provider_tier')->when($context->providerTier !== null, static fn (Builder $inner) => $inner->orWhere('provider_tier', $context->providerTier)))
            ->where(static fn (Builder $query) => $query->whereNull('campaign_code')->when($context->campaignCode !== null, static fn (Builder $inner) => $inner->orWhere('campaign_code', $context->campaignCode)))
            ->get();
    }

    public function addVersion(CommissionRule $rule, array $attributes): CommissionRuleVersion
    {
        return CommissionRuleVersion::query()->create($attributes + ['commission_rule_id' => $rule->id]);
    }

    public function nextVersionNumber(CommissionRule $rule): int
    {
        return ((int) CommissionRuleVersion::query()->where('commission_rule_id', $rule->id)->max('version')) + 1;
    }

    public function bookingCount(CommissionRule $rule): int
    {
        return (int) DB::table('bookings')->where('commission_rule_id', $rule->id)->count();
    }
}
