<?php

namespace App\Models;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The text of one event on one channel in one language, with `{variable}` placeholders. Arabic is the fallback when a
 * person's language has no active template. Each save bumps `version`; deliveries keep the version they were built from.
 *
 * @property int $id
 * @property string $event_key
 * @property NotificationChannel $channel
 * @property string $locale
 * @property string|null $title
 * @property string $body
 * @property int $version
 * @property bool $active
 * @property int $lock_version
 * @property int|null $updated_by_admin_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Admin|null $updatedBy
 */
class NotificationTemplate extends Model
{
    use Auditable;

    protected $fillable = ['event_key', 'channel', 'locale', 'title', 'body', 'version', 'active', 'lock_version', 'updated_by_admin_id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['version' => 1, 'active' => true, 'lock_version' => 0];

    public function event(): ?NotificationEvent
    {
        return NotificationEvent::tryFrom($this->event_key);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id')->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['channel' => NotificationChannel::class, 'active' => 'boolean'];
    }
}
