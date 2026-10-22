<?php

namespace App\Repositories\Contracts\Notification;

use App\DTOs\Notification\PushCampaignFilterData;
use App\Models\PushCampaign;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<PushCampaign>
 */
interface PushCampaignRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<PushCampaign>
     */
    public function datatableQuery(PushCampaignFilterData $filters): Builder;

    /**
     * @return Collection<int, PushCampaign> scheduled campaigns whose time has come
     */
    public function due(CarbonInterface $now, int $limit): Collection;
}
