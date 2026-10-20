<?php

namespace App\Repositories\Contracts\Identity;

use App\Enums\Identity\ClientApp;
use App\Models\User;
use App\Models\UserDevice;

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

    public function clearPushToken(User $user, ClientApp $app, string $deviceId): int;
}
