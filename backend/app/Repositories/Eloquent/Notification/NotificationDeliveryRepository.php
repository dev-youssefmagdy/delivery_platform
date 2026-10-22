<?php

namespace App\Repositories\Eloquent\Notification;

use App\DTOs\Notification\NotificationDeliveryFilterData;
use App\Enums\Notification\DeliveryStatus;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Models\NotificationDelivery;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<NotificationDelivery>
 */
final class NotificationDeliveryRepository extends BaseRepository implements NotificationDeliveryRepositoryInterface
{
    public function __construct(NotificationDelivery $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(NotificationDeliveryFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->channels !== [], static fn (Builder $query) => $query->whereIn('channel', array_map(static fn (NotificationChannel $case): string => $case->value, $filters->channels)))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (DeliveryStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->events !== [], static fn (Builder $query) => $query->whereIn('event_key', array_map(static fn (NotificationEvent $case): string => $case->value, $filters->events)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('created_at', '<=', $filters->to?->endOfDay()));
    }

    public function overdue(CarbonInterface $before, int $limit): Collection
    {
        return $this->query()
            ->whereIn('status', [DeliveryStatus::Queued->value, DeliveryStatus::Sending->value])
            ->where(static fn (Builder $query) => $query->where('next_attempt_at', '<=', $before)->orWhere(static fn (Builder $inner) => $inner->whereNull('next_attempt_at')->where('created_at', '<=', $before)))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
