<?php

namespace App\Models;

use App\Enums\Notification\DeliveryStatus;
use App\Enums\Notification\NotificationChannel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One channel's attempt to reach the recipient of an inbox entry, with its status, attempts and the provider's answer.
 * `notification_id + channel` is unique, so the same event can never be sent twice on a channel.
 *
 * @property int $id
 * @property string $uuid
 * @property int $notification_id
 * @property string $recipient_type
 * @property int $recipient_id
 * @property string $event_key
 * @property NotificationChannel $channel
 * @property DeliveryStatus $status
 * @property string|null $provider
 * @property string|null $provider_message_id
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int $attempts
 * @property Carbon|null $last_attempt_at
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property int|null $template_id
 * @property int|null $template_version
 * @property string|null $title
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AppNotification $notification
 */
class NotificationDelivery extends Model
{
    use HasUuids;

    protected $fillable = [
        'notification_id', 'recipient_type', 'recipient_id', 'event_key', 'channel', 'status', 'provider', 'provider_message_id',
        'error_code', 'error_message', 'attempts', 'last_attempt_at', 'next_attempt_at', 'sent_at', 'delivered_at',
        'template_id', 'template_version', 'title', 'body',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'queued', 'attempts' => 0];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<AppNotification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(AppNotification::class, 'notification_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => DeliveryStatus::class,
            'last_attempt_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
