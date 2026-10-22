<?php

namespace App\Models;

use Database\Factories\CustomerMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An in-app help message sent to a customer by support. The customer reads it through `GET /me/messages`; the
 * notification platform adds an inbox entry and a push that opens it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $sent_by_admin_id
 * @property string $subject
 * @property string $body
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property-read Admin|null $sender
 */
class CustomerMessage extends Model
{
    /** @use HasFactory<CustomerMessageFactory> */
    use HasFactory;

    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'sent_by_admin_id', 'subject', 'body', 'read_at'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sent_by_admin_id')->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
