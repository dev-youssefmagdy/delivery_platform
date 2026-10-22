<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentMethodType;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A saved online payment method: a gateway token reference (encrypted, never serialised) with brand and last four digits
 * for display. A card number never reaches the platform.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property PaymentMethodType $type
 * @property PaymentGatewayName $gateway
 * @property string $status pending|active
 * @property string|null $setup_reference
 * @property string|null $gateway_token
 * @property string|null $brand
 * @property string|null $last4
 * @property bool $is_default
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class PaymentMethod extends Model
{
    use Auditable;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    /**
     * @var list<string>
     */
    protected $fillable = ['uuid', 'user_id', 'type', 'gateway', 'status', 'setup_reference', 'gateway_token', 'brand', 'last4', 'is_default'];

    /**
     * @var list<string>
     */
    protected $hidden = ['gateway_token', 'setup_reference'];

    protected static function booted(): void
    {
        static::creating(static function (self $method): void {
            $method->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'gateway' => PaymentGatewayName::class,
            'gateway_token' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }
}
