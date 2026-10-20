<?php

namespace App\Models;

use App\Enums\Identity\ClientApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Immutable proof that a user accepted an exact policy version (SRS §6.2 step 7, §13.3).
 *
 * @property int $id
 * @property int $user_id
 * @property int $policy_version_id
 * @property string $locale
 * @property ClientApp $app
 * @property string|null $ip
 * @property Carbon $accepted_at
 * @property-read PolicyVersion $policyVersion
 */
class UserConsent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'policy_version_id', 'locale', 'app', 'ip', 'accepted_at'];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Consents are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Consents are append-only.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PolicyVersion, $this>
     */
    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app' => ClientApp::class,
            'accepted_at' => 'datetime',
        ];
    }
}
