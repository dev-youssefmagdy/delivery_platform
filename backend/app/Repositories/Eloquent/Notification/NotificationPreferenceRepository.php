<?php

namespace App\Repositories\Eloquent\Notification;

use App\Enums\Identity\UserStatus;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Repositories\Contracts\Notification\NotificationPreferenceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

final class NotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(private readonly NotificationPreference $model, private readonly User $users) {}

    public function forUser(User $user): Collection
    {
        return $this->model->newQuery()->where('user_id', $user->id)->get();
    }

    public function upsert(User $user, NotificationCategory $category, NotificationChannel $channel, bool $enabled): NotificationPreference
    {
        return $this->model->newQuery()->updateOrCreate(
            ['user_id' => $user->id, 'category' => $category->value, 'channel' => $channel->value],
            ['enabled' => $enabled],
        );
    }

    public function optedInUsersWithDevice(NotificationCategory $category, NotificationChannel $channel, array $apps): LazyCollection
    {
        return $this->optedIn($category, $channel, $apps)->orderBy('users.id')->cursor();
    }

    public function countOptedInUsersWithDevice(NotificationCategory $category, NotificationChannel $channel, array $apps): int
    {
        return $this->optedIn($category, $channel, $apps)->count();
    }

    /**
     * @param list<\App\Enums\Identity\ClientApp> $apps
     * @return Builder<User>
     */
    private function optedIn(NotificationCategory $category, NotificationChannel $channel, array $apps): Builder
    {
        $appValues = array_map(static fn ($app): string => $app->value, $apps);

        return $this->users->newQuery()
            ->where('users.status', UserStatus::Active->value)
            ->whereNull('users.anonymised_at')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('notification_preferences as np')
                ->whereColumn('np.user_id', 'users.id')
                ->where('np.category', $category->value)
                ->where('np.channel', $channel->value)
                ->where('np.enabled', true))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('user_devices as ud')
                ->whereColumn('ud.user_id', 'users.id')
                ->whereIn('ud.app', $appValues)
                ->whereNotNull('ud.push_token'));
    }
}
