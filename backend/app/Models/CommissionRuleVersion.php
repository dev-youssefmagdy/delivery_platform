<?php

namespace App\Models;

use App\Enums\Finance\CommissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One immutable version of a commission rule's numbers. A change of the numbers adds a version; a booking keeps a
 * snapshot of the version that applied to it (Plan I4, SRS §12.4).
 *
 * @property int $id
 * @property int $commission_rule_id
 * @property int $version
 * @property CommissionType $type
 * @property int $percent_bp
 * @property int $fixed_minor
 * @property int|null $min_minor
 * @property int|null $max_minor
 * @property bool $commission_on_tips
 * @property int|null $created_by_id
 * @property string|null $reason
 * @property Carbon $created_at
 * @property-read CommissionRule $rule
 */
class CommissionRuleVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'commission_rule_id', 'version', 'type', 'percent_bp', 'fixed_minor', 'min_minor', 'max_minor', 'commission_on_tips', 'created_by_id', 'reason',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Commission rule versions are immutable: add a new version.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Commission rule versions are immutable.');
        });
    }

    /**
     * @return BelongsTo<CommissionRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => CommissionType::class,
            'version' => 'integer',
            'percent_bp' => 'integer',
            'fixed_minor' => 'integer',
            'min_minor' => 'integer',
            'max_minor' => 'integer',
            'commission_on_tips' => 'boolean',
            'created_by_id' => 'integer',
        ];
    }
}
