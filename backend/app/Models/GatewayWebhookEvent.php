<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\WebhookEventKind;
use App\Enums\Payments\WebhookOutcome;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A callback received from a payment gateway. (gateway, event_id) is unique, so a second delivery is recognised as a
 * duplicate and never applied twice. The payload is the gateway's own JSON with secrets removed by the driver.
 *
 * @property int $id
 * @property string $uuid
 * @property PaymentGatewayName $gateway
 * @property string $event_id
 * @property WebhookEventKind|null $kind
 * @property bool $signature_valid
 * @property string $payload_hash
 * @property array<string, mixed>|null $payload
 * @property string|null $gateway_reference
 * @property int|null $payment_id
 * @property Carbon|null $occurred_at
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 * @property WebhookOutcome $outcome
 * @property string|null $error
 * @property int $attempts
 * @property int|null $reprocessed_by_id
 * @property string|null $reprocess_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Payment|null $payment
 */
class GatewayWebhookEvent extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'gateway', 'event_id', 'kind', 'signature_valid', 'payload_hash', 'payload', 'gateway_reference', 'payment_id', 'occurred_at',
        'received_at', 'processed_at', 'outcome', 'error', 'attempts', 'reprocessed_by_id', 'reprocess_reason',
    ];

    /**
     * @var list<string>
     */
    protected array $auditExcluded = ['payload'];

    protected static function booted(): void
    {
        static::creating(static function (self $event): void {
            $event->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
            'gateway' => PaymentGatewayName::class,
            'kind' => WebhookEventKind::class,
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'outcome' => WebhookOutcome::class,
            'attempts' => 'integer',
        ];
    }
}
