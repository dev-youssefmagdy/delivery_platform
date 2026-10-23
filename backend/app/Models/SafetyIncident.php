<?php

namespace App\Models;

use App\Enums\Identity\ClientApp;
use App\Enums\Safety\IncidentCategory;
use App\Enums\Safety\IncidentEscalationLevel;
use App\Enums\Safety\IncidentOutcome;
use App\Enums\Safety\IncidentPriority;
use App\Enums\Safety\IncidentSeverity;
use App\Enums\Safety\IncidentStatus;
use App\Enums\Safety\IncidentTrigger;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A safety report or SOS raised from an app (SRS §13, §16.10). The narrative, callback phone and location are evidence: encrypted
 * at rest and shown to staff holding `incidents.view_evidence` only, every time audited. A case with a legal hold survives retention
 * clean-up and account deletion.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $number
 * @property int|null $booking_id
 * @property int $reporter_user_id
 * @property ClientApp $reporter_type
 * @property IncidentTrigger $trigger
 * @property IncidentCategory $category
 * @property IncidentSeverity $severity
 * @property IncidentPriority $priority
 * @property IncidentStatus $status
 * @property string|null $narrative
 * @property string|null $callback_phone_encrypted
 * @property array<string, mixed>|null $location_snapshot
 * @property array<string, mixed>|null $context
 * @property Carbon $occurred_at
 * @property int|null $assigned_admin_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $sla_due_at
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $sla_breached_at
 * @property IncidentEscalationLevel|null $escalation_level
 * @property Carbon|null $escalated_at
 * @property IncidentOutcome|null $outcome
 * @property string|null $resolution_reason
 * @property Carbon|null $resolved_at
 * @property bool $legal_hold
 * @property Carbon|null $legal_hold_at
 * @property int|null $legal_hold_by_admin_id
 * @property string|null $legal_hold_reason
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Booking|null $booking
 * @property-read User $reporter
 * @property-read Admin|null $assignedAdmin
 */
class SafetyIncident extends Model
{
    use Auditable;
    use HasUuids;

    protected $fillable = [
        'number', 'booking_id', 'reporter_user_id', 'reporter_type', 'trigger', 'category', 'severity', 'priority', 'status', 'narrative',
        'callback_phone_encrypted', 'location_snapshot', 'context', 'occurred_at', 'assigned_admin_id', 'assigned_at', 'sla_due_at',
        'acknowledged_at', 'sla_breached_at', 'escalation_level', 'escalated_at', 'outcome', 'resolution_reason', 'resolved_at',
        'legal_hold', 'legal_hold_at', 'legal_hold_by_admin_id', 'legal_hold_reason', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'new', 'legal_hold' => false, 'lock_version' => 0];

    /**
     * Evidence never goes into an audit snapshot.
     *
     * @var list<string>
     */
    protected array $auditExcluded = ['narrative', 'callback_phone_encrypted', 'location_snapshot', 'context', 'resolution_reason', 'legal_hold_reason'];

    protected $hidden = ['narrative', 'callback_phone_encrypted', 'location_snapshot'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isSlaBreached(): bool
    {
        return $this->status === IncidentStatus::New && $this->sla_due_at !== null && $this->sla_due_at->isPast();
    }

    public function callbackPhone(): ?string
    {
        return $this->callback_phone_encrypted === null ? null : (string) decrypt($this->callback_phone_encrypted);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id')->withTrashed();
    }

    /**
     * @return HasMany<SafetyIncidentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SafetyIncidentEvent::class, 'incident_id');
    }

    /**
     * @return HasMany<SafetyIncidentAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SafetyIncidentAttachment::class, 'incident_id');
    }

    /**
     * @return HasMany<TrustedContactAction, $this>
     */
    public function contactActions(): HasMany
    {
        return $this->hasMany(TrustedContactAction::class, 'incident_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'reporter_type' => ClientApp::class,
            'trigger' => IncidentTrigger::class,
            'category' => IncidentCategory::class,
            'severity' => IncidentSeverity::class,
            'priority' => IncidentPriority::class,
            'status' => IncidentStatus::class,
            'escalation_level' => IncidentEscalationLevel::class,
            'outcome' => IncidentOutcome::class,
            'narrative' => 'encrypted',
            'location_snapshot' => 'encrypted:array',
            'context' => 'array',
            'legal_hold' => 'boolean',
            'occurred_at' => 'datetime',
            'assigned_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'legal_hold_at' => 'datetime',
        ];
    }
}
