<?php

namespace App\Models;

use App\Enums\Provider\ProviderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One row of a provider's status history (append-only): who moved the application from which status to which, and why.
 *
 * @property int $id
 * @property int $provider_profile_id
 * @property ProviderStatus|null $from_status
 * @property ProviderStatus $to_status
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string|null $reason
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class ProviderStatusEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(static fn () => throw new LogicException('Provider status events are append-only.'));
        static::deleting(static fn () => throw new LogicException('Provider status events are append-only.'));
    }

    /**
     * The admin behind the event (meaningful only when `actor_type` is `admin`).
     *
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_id');
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['from_status' => ProviderStatus::class, 'to_status' => ProviderStatus::class, 'meta' => 'array'];
    }
}
