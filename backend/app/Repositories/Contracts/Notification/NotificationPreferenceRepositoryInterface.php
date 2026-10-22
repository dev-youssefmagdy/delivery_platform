<?php

namespace App\Repositories\Contracts\Notification;

use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

interface NotificationPreferenceRepositoryInterface
{
    /**
     * @return Collection<int, NotificationPreference>
     */
    public function forUser(User $user): Collection;

    public function upsert(User $user, NotificationCategory $category, NotificationChannel $channel, bool $enabled): NotificationPreference;

    /**
     * Users who explicitly enabled the category on the channel and have a push token in one of the apps.
     *
     * @param list<ClientApp> $apps
     * @return LazyCollection<int, User>
     */
    public function optedInUsersWithDevice(NotificationCategory $category, NotificationChannel $channel, array $apps): LazyCollection;

    /**
     * @param list<ClientApp> $apps
     */
    public function countOptedInUsersWithDevice(NotificationCategory $category, NotificationChannel $channel, array $apps): int;
}
