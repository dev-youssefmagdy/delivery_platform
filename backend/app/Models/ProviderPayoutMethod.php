<?php

namespace App\Models;

use App\Enums\Payout\PayoutMethodType;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider's payout destination. `details` (holder, bank, account / wallet number) is encrypted at rest and hidden from
 * serialisation and the audit log; screens and the API only ever show the type, the label and `last4`. Removing a method
 * soft-deletes it so past payouts keep their link (they also hold a masked snapshot).
 *
 * @property int $id
 * @property string $uuid
 * @property int $provider_profile_id
 * @property PayoutMethodType $type
 * @property string $label
 * @property array<string, string> $details
 * @property string|null $last4
 * @property bool $is_default
 * @property Carbon|null $verified_at
 * @property int|null $verified_by_id
 * @property string|null $verification_note
 * @property-read ProviderProfile $provider
 */
class ProviderPayoutMethod extends Model
{
    use Auditable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = ['uuid', 'provider_profile_id', 'type', 'label', 'details', 'last4', 'is_default', 'verified_at', 'verified_by_id', 'verification_note'];

    /**
     * @var list<string>
     */
    protected $hidden = ['details'];

    protected static function booted(): void
    {
        static::creating(static function (self $method): void {
            $method->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_profile_id');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * What screens, snapshots and the API may show about the method.
     *
     * @return array{type: string, label: string, last4: string|null}
     */
    public function masked(): array
    {
        return ['type' => $this->type->value, 'label' => $this->label, 'last4' => $this->last4];
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
            'type' => PayoutMethodType::class,
            'details' => 'encrypted:array',
            'is_default' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
