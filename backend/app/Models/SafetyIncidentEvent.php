<?php

namespace App\Models;

use App\Enums\Common\ActorType;
use App\Enums\Safety\IncidentEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only timeline of a safety case. `body` (staff notes, messages to and from the person) is encrypted at rest.
 *
 * @property int $id
 * @property int $incident_id
 * @property IncidentEventType $type
 * @property ActorType $actor_type
 * @property int|null $actor_id
 * @property string|null $body
 * @property bool $is_public
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class SafetyIncidentEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['incident_id', 'type', 'actor_type', 'actor_id', 'body', 'is_public', 'meta'];

    protected $hidden = ['body'];

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Safety incident events are append-only.');
        });
    }

    /**
     * @return BelongsTo<SafetyIncident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class, 'incident_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => IncidentEventType::class,
            'actor_type' => ActorType::class,
            'body' => 'encrypted',
            'is_public' => 'boolean',
            'meta' => 'array',
        ];
    }
}
