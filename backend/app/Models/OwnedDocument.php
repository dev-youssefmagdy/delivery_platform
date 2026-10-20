<?php

namespace App\Models;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\FileScanStatus;
use App\Support\Audit\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Shared shape of `provider_documents` and `vehicle_documents`: a private file (never a public path), its hash and
 * scan result, an ENCRYPTED document number, validity dates and the review outcome. A replaced pending upload is
 * soft-deleted (its file removed); reviewed documents are kept as history.
 *
 * @property int $id
 * @property string $uuid
 * @property int $document_type_id
 * @property string|null $path
 * @property string|null $original_name
 * @property string $mime
 * @property int $size_bytes
 * @property string $file_hash
 * @property FileScanStatus $scan_status
 * @property string|null $number decrypted on read
 * @property Carbon|null $issued_at
 * @property Carbon|null $expires_at
 * @property DocumentStatus $status
 * @property int|null $reviewed_by_admin_id
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property int|null $expiry_reminder_days smallest reminder threshold already notified for this document
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read DocumentType $documentType
 */
abstract class OwnedDocument extends Model
{
    use Auditable;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Neither the number nor the storage path nor the file name belongs in the audit log.
     *
     * @var list<string>
     */
    protected array $auditExcluded = ['number', 'path', 'original_name'];

    /**
     * @var list<string>
     */
    protected $hidden = ['number', 'path'];

    protected static function booted(): void
    {
        static::creating(static function (self $document): void {
            $document->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    /**
     * The record admin audit entries about this document are written against (the provider or the vehicle).
     */
    abstract public function auditTarget(): Model;

    /**
     * The provider this document belongs to (directly, or through its vehicle).
     */
    abstract public function ownerProfile(): ProviderProfile;

    /**
     * True when the document counts as valid today: approved and not past its expiry date.
     */
    public function isValidOn(CarbonInterface $day): bool
    {
        return $this->status === DocumentStatus::Approved && ($this->expires_at === null || $this->expires_at->greaterThanOrEqualTo($day->copy()->startOfDay()));
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'scan_status' => FileScanStatus::class,
            'status' => DocumentStatus::class,
            'number' => 'encrypted',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
            'size_bytes' => 'integer',
            'expiry_reminder_days' => 'integer',
        ];
    }
}
