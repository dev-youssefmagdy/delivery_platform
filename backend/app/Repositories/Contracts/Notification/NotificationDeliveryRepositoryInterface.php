<?php

namespace App\Repositories\Contracts\Notification;

use App\DTOs\Notification\NotificationDeliveryFilterData;
use App\Models\NotificationDelivery;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<NotificationDelivery>
 */
interface NotificationDeliveryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<NotificationDelivery>
     */
    public function datatableQuery(NotificationDeliveryFilterData $filters): Builder;

    /**
     * Deliveries that should have been attempted by now but were not (a lost job): queued or sending and overdue.
     *
     * @return Collection<int, NotificationDelivery>
     */
    public function overdue(CarbonInterface $before, int $limit): Collection;
}
