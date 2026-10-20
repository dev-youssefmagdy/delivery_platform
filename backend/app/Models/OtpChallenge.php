<?php

namespace App\Models;

use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpChallengeState;
use App\Enums\Identity\OtpDeliveryStatus;
use App\Enums\Identity\OtpPurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One OTP request. Holds an HMAC of the code (never the code) and a keyed hash of the phone; the masked phone is
 * only for the system-admin log. Rows are written by OtpService only.
 *
 * @property int $id
 * @property string $phone_hash
 * @property string $phone_masked
 * @property OtpPurpose $purpose
 * @property ClientApp $app
 * @property string $code_hash
 * @property int $attempts
 * @property int $max_attempts
 * @property OtpDeliveryStatus $delivery_status
 * @property string|null $delivery_driver
 * @property Carbon $expires_at
 * @property Carbon $resend_available_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $invalidated_at
 * @property string|null $ip
 * @property string|null $device_id
 * @property Carbon|null $created_at
 */
class OtpChallenge extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'phone_hash', 'phone_masked', 'purpose', 'app', 'code_hash', 'attempts', 'max_attempts', 'delivery_status',
        'delivery_driver', 'expires_at', 'resend_available_at', 'consumed_at', 'invalidated_at', 'ip', 'device_id',
    ];

    protected $hidden = ['code_hash', 'phone_hash'];

    /**
     * Challenges are only useful for a short time and for the support log; keep 30 days.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(30));
    }

    public function state(?Carbon $now = null): OtpChallengeState
    {
        $now ??= Carbon::now();

        return match (true) {
            $this->consumed_at !== null => OtpChallengeState::Verified,
            $this->invalidated_at !== null => OtpChallengeState::Superseded,
            $this->attempts >= $this->max_attempts => OtpChallengeState::Locked,
            $this->expires_at->lte($now) => OtpChallengeState::Expired,
            default => OtpChallengeState::Pending,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'app' => ClientApp::class,
            'delivery_status' => OtpDeliveryStatus::class,
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
