<?php

namespace App\Models;

use App\Enums\Identity\ClientApp;
use App\Enums\Support\TicketCategory;
use App\Enums\Support\TicketGroup;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketSource;
use App\Enums\Support\TicketStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A support ticket or dispute (SRS §14.3, §16.11) opened by a customer or a provider, optionally linked to a booking, payment or
 * payout. Closing or resolving never changes money: refunds and wallet adjustments are separate, permissioned finance flows
 * that a ticket only links to.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $number
 * @property int $user_id
 * @property ClientApp $app
 * @property TicketCategory $category
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property TicketSource $source
 * @property string $subject
 * @property int|null $booking_id
 * @property int|null $payment_id
 * @property int|null $payout_request_id
 * @property TicketGroup $group
 * @property int|null $assigned_admin_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $sla_due_at
 * @property Carbon|null $first_response_at
 * @property Carbon|null $sla_breached_at
 * @property Carbon|null $last_message_at
 * @property Carbon|null $last_staff_reply_at
 * @property Carbon|null $escalated_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property string|null $resolution_reason
 * @property array<string, mixed>|null $context
 * @property int|null $source_report_id
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Booking|null $booking
 * @property-read Admin|null $assignedAdmin
 */
class SupportTicket extends Model
{
    use Auditable;
    use HasUuids;

    protected $fillable = [
        'number', 'user_id', 'app', 'category', 'priority', 'status', 'source', 'subject', 'booking_id', 'payment_id', 'payout_request_id',
        'group', 'assigned_admin_id', 'assigned_at', 'sla_due_at', 'first_response_at', 'sla_breached_at', 'last_message_at',
        'last_staff_reply_at', 'escalated_at', 'resolved_at', 'closed_at', 'resolution_reason', 'context', 'source_report_id', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'open', 'source' => 'app', 'group' => 'support', 'lock_version' => 0];

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
        return $this->status->isActive() && $this->first_response_at === null && $this->sla_due_at !== null && $this->sla_due_at->isPast();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<PayoutRequest, $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class, 'payout_request_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id')->withTrashed();
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }

    /**
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    /**
     * @return HasMany<TicketEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'ticket_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'app' => ClientApp::class,
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'source' => TicketSource::class,
            'group' => TicketGroup::class,
            'assigned_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'last_message_at' => 'datetime',
            'last_staff_reply_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'context' => 'array',
        ];
    }
}
