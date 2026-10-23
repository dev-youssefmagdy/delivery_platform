<?php

namespace App\Repositories\Eloquent\Notification;

use App\DTOs\Notification\NotificationTemplateFilterData;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Models\NotificationTemplate;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<NotificationTemplate>
 */
final class NotificationTemplateRepository extends BaseRepository implements NotificationTemplateRepositoryInterface
{
    public function __construct(NotificationTemplate $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(NotificationTemplateFilterData $filters): Builder
    {
        return $this->query()
            ->with('updatedBy:id,name')
            ->when($filters->eventKey !== null, static fn (Builder $query) => $query->where('event_key', 'like', '%'.$filters->eventKey.'%'))
            ->when($filters->categories !== [], function (Builder $query) use ($filters): void {
                $keys = array_values(array_map(
                    static fn (NotificationEvent $event): string => $event->value,
                    array_filter(NotificationEvent::cases(), static fn (NotificationEvent $event): bool => in_array($event->category(), $filters->categories, true)),
                ));
                $query->whereIn('event_key', $keys);
            })
            ->when($filters->channels !== [], static fn (Builder $query) => $query->whereIn('channel', array_map(static fn (NotificationChannel $case): string => $case->value, $filters->channels)))
            ->when($filters->locales !== [], static fn (Builder $query) => $query->whereIn('locale', array_map(static fn ($case): string => $case->value, $filters->locales)))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }

    public function findFor(string $eventKey, NotificationChannel $channel, string $locale): ?NotificationTemplate
    {
        return $this->query()->where('event_key', $eventKey)->where('channel', $channel->value)->where('locale', $locale)->first();
    }

    public function siblings(NotificationTemplate $template): Collection
    {
        return $this->query()->where('event_key', $template->event_key)->where('channel', $template->channel->value)->orderBy('locale')->get();
    }
}
