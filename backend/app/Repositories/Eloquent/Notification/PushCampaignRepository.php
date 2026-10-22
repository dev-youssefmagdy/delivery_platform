<?php

namespace App\Repositories\Eloquent\Notification;

use App\DTOs\Notification\PushCampaignFilterData;
use App\Enums\Notification\CampaignAudience;
use App\Enums\Notification\CampaignStatus;
use App\Models\PushCampaign;
use App\Repositories\Contracts\Notification\PushCampaignRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<PushCampaign>
 */
final class PushCampaignRepository extends BaseRepository implements PushCampaignRepositoryInterface
{
    public function __construct(PushCampaign $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(PushCampaignFilterData $filters): Builder
    {
        return $this->query()
            ->with('createdBy:id,name')
            ->when($filters->name !== null, static fn (Builder $query) => $query->where('name', 'like', '%'.$filters->name.'%'))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (CampaignStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->audiences !== [], static fn (Builder $query) => $query->whereIn('audience', array_map(static fn (CampaignAudience $case): string => $case->value, $filters->audiences)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('created_at', '<=', $filters->to?->endOfDay()));
    }

    public function due(CarbonInterface $now, int $limit): Collection
    {
        return $this->query()
            ->where('status', CampaignStatus::Scheduled->value)
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }
}
