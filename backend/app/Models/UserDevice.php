<?php

namespace App\Models;

use App\Enums\Common\Platform;
use App\Enums\Identity\ClientApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property ClientApp $app
 * @property Platform $platform
 * @property string $device_id
 * @property string|null $push_token
 * @property string|null $push_token_hash
 * @property string|null $app_version
 * @property string|null $locale
 * @property Carbon|null $last_active_at
 */
class UserDevice extends Model
{
    protected $fillable = ['user_id', 'app', 'platform', 'device_id', 'push_token', 'push_token_hash', 'app_version', 'locale', 'last_active_at'];

    protected $hidden = ['push_token', 'push_token_hash'];

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
            'app' => ClientApp::class,
            'platform' => Platform::class,
            'last_active_at' => 'datetime',
        ];
    }
}
