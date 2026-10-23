<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A loyalty level reached by completed-job count (SRS 11.3). Reaching it may grant a one-time credit with an expiry.
 *
 * @property int $id
 * @property string $name
 * @property string|null $name_en
 * @property int $min_completed_jobs
 * @property int $reward_minor
 * @property string $currency
 * @property int $credit_valid_days
 * @property int $sort
 * @property bool $is_active
 * @property string|null $status_reason
 * @property int $lock_version
 * @property Carbon $created_at
 */
class LoyaltyLevel extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = ['name', 'name_en', 'min_completed_jobs', 'reward_minor', 'currency', 'credit_valid_days', 'sort', 'is_active', 'status_reason', 'lock_version'];

    /** @var array<string, mixed> */
    protected $attributes = ['is_active' => true, 'lock_version' => 0, 'reward_minor' => 0];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'min_completed_jobs' => 'integer', 'reward_minor' => 'integer'];
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'en' && $this->name_en !== null && $this->name_en !== '' ? $this->name_en : $this->name;
    }
}
