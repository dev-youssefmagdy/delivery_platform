<?php

namespace App\Models;

use App\Enums\Growth\ReferralAudience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The unique, shareable referral code of a user for one audience.
 *
 * @property int $id
 * @property int $user_id
 * @property ReferralAudience $audience
 * @property string $code
 */
class ReferralCode extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'audience', 'code'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['audience' => ReferralAudience::class];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
