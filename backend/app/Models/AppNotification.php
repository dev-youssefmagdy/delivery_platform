<?php

namespace App\Models;

use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationEvent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One inbox entry (SRS §14.1): what a person sees in the app and the admin bell, and the parent of its per-channel
 * deliveries. The text is rendered once, in the person's language, when the event happens.
 *
 * @property int $id
 * @property string $uuid
 * @property string $recipient_type
 * @property int $recipient_id
 * @property ClientApp|null $app
 * @property string $event_key
 * @property NotificationCategory $category
 * @property string $title
 * @property string $body
 * @property string $locale
 * @property string|null $link_type
 * @property string|null $link_id
 * @property array<string, mixed>|null $data
 * @property string|null $dedupe_key
 * @property int|null $campaign_id
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotificationDelivery> $deliveries
 */
class AppNotification extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'recipient_type', 'recipient_id', 'app', 'event_key', 'category', 'title', 'body', 'locale', 'link_type', 'link_id',
        'data', 'dedupe_key', 'campaign_id', 'read_at',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function event(): ?NotificationEvent
    {
        return NotificationEvent::tryFrom($this->event_key);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<NotificationDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app' => ClientApp::class,
            'category' => NotificationCategory::class,
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
