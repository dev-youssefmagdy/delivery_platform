<?php

namespace App\Repositories\Contracts\Identity;

use App\Enums\Identity\ClientApp;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface UserDeviceRepositoryInterface
{
    /**
     * Creates or refreshes the (user, app, device) row.
     *
     * @param array<string, mixed> $attributes
     */
    public function upsert(User $user, ClientApp $app, string $deviceId, array $attributes): UserDevice;

    /**
     * A push token belongs to one physical install: clears it from every other row that holds the same token.
     */
    public function releasePushToken(string $pushTokenHash, UserDevice $except): int;

    /**
     * Devices of the user that hold a push token, optionally only those of one app.
     *
     * @return Collection<int, UserDevice>
     */
    public function withPushToken(User $user, ?ClientApp $app): Collection;

    /**
     * Forgets a token the push provider reported as invalid (by its hash).
     */
    public function forgetPushTokenByHash(string $pushTokenHash): int;

    public function clearPushToken(User $user, ClientApp $app, string $deviceId): int;

    /**
     * @return Builder<UserDevice> the user's devices, newest activity first (admin customer tab)
     */
    public function datatableQuery(User $user): Builder;

    /**
     * Deletes every device row (push tokens included) of the user.
     */
    public function deleteForUser(User $user): int;
}
