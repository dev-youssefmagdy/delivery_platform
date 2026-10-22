<?php

namespace App\Repositories\Eloquent\Identity;

use App\Enums\Identity\ClientApp;
use App\Models\User;
use App\Models\UserDevice;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class UserDeviceRepository implements UserDeviceRepositoryInterface
{
    public function __construct(private readonly UserDevice $model) {}

    public function upsert(User $user, ClientApp $app, string $deviceId, array $attributes): UserDevice
    {
        return $this->model->newQuery()->updateOrCreate(
            ['user_id' => $user->id, 'app' => $app->value, 'device_id' => $deviceId],
            $attributes,
        );
    }

    public function releasePushToken(string $pushTokenHash, UserDevice $except): int
    {
        return $this->model->newQuery()
            ->where('push_token_hash', $pushTokenHash)
            ->whereKeyNot($except->getKey())
            ->update(['push_token' => null, 'push_token_hash' => null]);
    }

    public function withPushToken(User $user, ?ClientApp $app): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereNotNull('push_token')
            ->when($app !== null, static fn (Builder $query) => $query->where('app', $app?->value))
            ->orderByDesc('last_active_at')
            ->get();
    }

    public function forgetPushTokenByHash(string $pushTokenHash): int
    {
        return $this->model->newQuery()->where('push_token_hash', $pushTokenHash)->update(['push_token' => null, 'push_token_hash' => null]);
    }

    public function clearPushToken(User $user, ClientApp $app, string $deviceId): int
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('app', $app->value)
            ->where('device_id', $deviceId)
            ->update(['push_token' => null, 'push_token_hash' => null]);
    }

    public function datatableQuery(User $user): Builder
    {
        return $this->model->newQuery()->where('user_id', $user->id);
    }

    public function deleteForUser(User $user): int
    {
        return $this->model->newQuery()->where('user_id', $user->id)->delete();
    }
}
