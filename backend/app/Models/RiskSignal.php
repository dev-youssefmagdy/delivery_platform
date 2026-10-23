<?php

namespace App\Models;

use App\Enums\Risk\RiskAction;
use App\Enums\Risk\RiskSignalStatus;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An internal, explainable abuse indicator about a customer or a provider (SRS §15). Never public, never an automatic sanction:
 * it is a review task. `evidence` holds counts, rates, window and booking codes (no contact data).
 *
 * @property int $id
 * @property string $uuid
 * @property RiskSignalType $type
 * @property RiskSubjectType $subject_type
 * @property int $subject_id
 * @property int $score
 * @property array<string, mixed> $evidence
 * @property RiskSignalStatus $status
 * @property int|null $active_marker
 * @property Carbon $first_detected_at
 * @property Carbon $last_evaluated_at
 * @property Carbon|null $escalated_at
 * @property int|null $resolved_by_admin_id
 * @property Carbon|null $resolved_at
 * @property RiskAction|null $resolution_action
 * @property string|null $resolution_reason
 * @property Carbon $created_at
 */
class RiskSignal extends Model
{
    use Auditable;

    protected $fillable = [
        'uuid', 'type', 'subject_type', 'subject_id', 'score', 'evidence', 'status', 'active_marker', 'first_detected_at', 'last_evaluated_at',
        'escalated_at', 'resolved_by_admin_id', 'resolved_at', 'resolution_action', 'resolution_reason',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $signal): void {
            $signal->uuid ??= (string) Str::uuid();
        });
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
            'type' => RiskSignalType::class,
            'subject_type' => RiskSubjectType::class,
            'status' => RiskSignalStatus::class,
            'resolution_action' => RiskAction::class,
            'evidence' => 'array',
            'score' => 'integer',
            'first_detected_at' => 'datetime',
            'last_evaluated_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
