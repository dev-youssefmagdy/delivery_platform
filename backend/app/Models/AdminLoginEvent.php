<?php

namespace App\Models;

use App\Enums\Admin\AdminLoginOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only login activity row (success, failure, throttled, logout).
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string|null $email
 * @property AdminLoginOutcome $outcome
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property-read Admin|null $admin
 */
class AdminLoginEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'email', 'outcome', 'ip', 'user_agent'];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Login events are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Login events are append-only.'));
    }

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
            'outcome' => AdminLoginOutcome::class,
            'created_at' => 'datetime',
        ];
    }
}
