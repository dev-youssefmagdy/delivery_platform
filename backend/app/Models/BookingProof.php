<?php

namespace App\Models;

use App\Enums\Catalog\ProofStage;
use App\Enums\Catalog\ProofType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * Evidence attached to a booking (SRS §7.5): a verified pickup PIN, photos, signatures, checklists, weights and verified recipient codes (P4.1). Append-only; files, when there are any, live on a private disk and are referenced here.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property ProofType $proof_type
 * @property ProofStage $stage
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string|null $storage_disk
 * @property string|null $storage_path
 * @property string|null $sha256
 * @property string|null $mime
 * @property int|null $size_bytes
 * @property string|null $scan_status
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 */
class BookingProof extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['uuid', 'booking_id', 'proof_type', 'stage', 'actor_type', 'actor_id', 'storage_disk', 'storage_path', 'sha256', 'mime', 'size_bytes', 'scan_status', 'metadata'];

    protected static function booted(): void
    {
        static::creating(static function (self $proof): void {
            $proof->uuid ??= (string) Str::uuid();
        });

        static::updating(static function (): void {
            throw new LogicException('Booking proofs are append-only.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Booking proofs are append-only.');
        });
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['proof_type' => ProofType::class, 'stage' => ProofStage::class, 'metadata' => 'array', 'created_at' => 'datetime'];
    }
}
