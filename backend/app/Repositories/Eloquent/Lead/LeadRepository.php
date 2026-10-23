<?php

namespace App\Repositories\Eloquent\Lead;

use App\DTOs\Lead\LeadFilterData;
use App\Enums\Lead\LeadStatus;
use App\Models\Lead;
use App\Repositories\Contracts\Lead\LeadRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class LeadRepository implements LeadRepositoryInterface
{
    public function findByDuplicateKey(string $key): ?Lead
    {
        return Lead::query()->where('duplicate_key', $key)->first();
    }

    public function createNumbered(array $attributes): Lead
    {
        $lead = Lead::query()->create($attributes);
        $lead->forceFill(['reference' => 'LD-'.str_pad((string) $lead->id, 6, '0', STR_PAD_LEFT)])->save();

        return $lead;
    }

    public function lockById(int $id): ?Lead
    {
        return Lead::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function update(Lead $lead, array $attributes): Lead
    {
        $lead->forceFill($attributes)->save();

        return $lead;
    }

    public function countByStatus(string $status): int
    {
        return Lead::query()->where('status', $status)->count();
    }

    public function datatableQuery(LeadFilterData $filters): Builder
    {
        return Lead::query()
            ->select('leads.*')
            ->with(['city:id,name', 'serviceCategory:id,name'])
            ->when($filters->cityIds !== [], static fn (Builder $q) => $q->whereIn('leads.city_id', $filters->cityIds))
            ->when($filters->serviceCategoryIds !== [], static fn (Builder $q) => $q->whereIn('leads.service_category_id', $filters->serviceCategoryIds))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('leads.status', array_map(static fn (LeadStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('leads.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('leads.created_at', '<=', $filters->to?->endOfDay()));
    }
}
