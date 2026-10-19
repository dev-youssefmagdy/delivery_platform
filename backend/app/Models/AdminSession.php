<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One authenticated browser session of an admin (list + revoke, ADR-06).
 *
 * @property int $id
 * @property int $admin_id
 * @property string $session_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $last_active_at
 * @property Carbon|null $created_at
 */
class AdminSession extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'session_id', 'ip', 'user_agent', 'last_active_at'];

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
