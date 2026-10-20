<?php

namespace App\Models;

use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property DeletionRequestStatus $status
 * @property UserStatus $previous_user_status
 * @property string|null $reason
 * @property Carbon $requested_at
 * @property Carbon|null $processed_at
 * @property int|null $processed_by_admin_id
 * @property string|null $processing_note
 */
class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'status', 'previous_user_status', 'reason', 'requested_at', 'processed_at', 'processed_by_admin_id', 'processing_note',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $request): void {
            $request->uuid ??= (string) Str::uuid();
        });
    }

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
            'status' => DeletionRequestStatus::class,
            'previous_user_status' => UserStatus::class,
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
