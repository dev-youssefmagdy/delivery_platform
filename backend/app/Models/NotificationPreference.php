<?php

namespace App\Models;

use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person's choice for one category on one channel. No row = the category's default.
 *
 * @property int $id
 * @property int $user_id
 * @property NotificationCategory $category
 * @property NotificationChannel $channel
 * @property bool $enabled
 */
class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'category', 'channel', 'enabled'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'channel' => NotificationChannel::class,
            'enabled' => 'boolean',
        ];
    }
}
