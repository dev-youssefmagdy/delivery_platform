<?php

namespace App\Services\Identity;

use App\DTOs\Identity\DeviceData;
use App\Enums\Identity\ClientApp;
use App\Models\User;
use App\Models\UserDevice;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Registers the install a user signs in from and keeps its push token and app version fresh.
 */
final class DeviceService
{
    public function __construct(private readonly UserDeviceRepositoryInterface $devices) {}

    public function register(User $user, ClientApp $app, DeviceData $data): UserDevice
    {
        return DB::transaction(function () use ($user, $app, $data): UserDevice {
            $attributes = [
                'platform' => $data->platform,
                'app_version' => $data->appVersion,
                'locale' => $data->locale,
                'last_active_at' => now(),
            ];

            if ($data->pushToken !== null && $data->pushToken !== '') {
                $attributes['push_token'] = $data->pushToken;
                $attributes['push_token_hash'] = hash('sha256', $data->pushToken);
            }

            $device = $this->devices->upsert($user, $app, Str::limit($data->deviceId, 100, ''), $attributes);

            if (isset($attributes['push_token_hash'])) {
                $this->devices->releasePushToken($attributes['push_token_hash'], $device);
            }

            return $device;
        });
    }

    public function forgetPushToken(User $user, ClientApp $app, string $deviceId): void
    {
        $this->devices->clearPushToken($user, $app, $deviceId);
    }
}
