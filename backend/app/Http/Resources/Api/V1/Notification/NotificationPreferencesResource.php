<?php

namespace App\Http\Resources\Api\V1\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The settings matrix from `NotificationPreferenceService::matrix()`: per category the channels with their state and
 * whether the channel is locked on (essential categories keep push and the inbox on).
 *
 * @property list<array{category: \App\Enums\Notification\NotificationCategory, essential: bool, opt_in: bool, channels: list<array{channel: \App\Enums\Notification\NotificationChannel, enabled: bool, locked: bool}>}> $resource
 */
class NotificationPreferencesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'categories' => array_map(static fn (array $row): array => [
                'category' => $row['category']->value,
                'label' => $row['category']->label(),
                'description' => $row['category']->description(),
                'essential' => $row['essential'],
                'opt_in_required' => $row['opt_in'],
                'channels' => array_map(static fn (array $channel): array => [
                    'channel' => $channel['channel']->value,
                    'enabled' => $channel['enabled'],
                    'locked' => $channel['locked'],
                ], $row['channels']),
            ], $this->resource),
        ];
    }
}
