<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer's loyalty counter and current level.
 *
 * @property int $id
 * @property int $user_id
 * @property int $completed_jobs
 * @property int|null $level_id
 * @property Carbon|null $level_reached_at
 * @property-read LoyaltyLevel|null $level
 */
class LoyaltyMember extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'completed_jobs', 'level_id', 'level_reached_at'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['completed_jobs' => 'integer', 'level_reached_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<LoyaltyLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(LoyaltyLevel::class, 'level_id');
    }
}
