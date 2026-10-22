<?php

namespace App\Repositories\Contracts\Notification;

use App\DTOs\Notification\NotificationTemplateFilterData;
use App\Enums\Notification\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<NotificationTemplate>
 */
interface NotificationTemplateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<NotificationTemplate>
     */
    public function datatableQuery(NotificationTemplateFilterData $filters): Builder;

    /**
     * The template of one event, channel and language, whether or not it is active.
     */
    public function findFor(string $eventKey, NotificationChannel $channel, string $locale): ?NotificationTemplate;

    /**
     * @return Collection<int, NotificationTemplate> every language of an event on a channel
     */
    public function siblings(NotificationTemplate $template): Collection;
}
