<?php

namespace App\Models;

use App\Enums\Provider\FileScanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Restricted evidence of a safety case. The file lives on the private disk and is served only through the admin's signed,
 * audited route (`incidents.view_evidence`) or to the person who uploaded it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $incident_id
 * @property string $uploader_type
 * @property int|null $uploader_id
 * @property string|null $path
 * @property string $mime
 * @property int $size_bytes
 * @property string $file_hash
 * @property FileScanStatus $scan_status
 * @property Carbon|null $purged_at
 * @property Carbon $created_at
 */
class SafetyIncidentAttachment extends Model
{
    use HasUuids;

    protected $fillable = ['incident_id', 'uploader_type', 'uploader_id', 'path', 'mime', 'size_bytes', 'file_hash', 'scan_status', 'purged_at'];

    protected $hidden = ['path'];

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
            'scan_status' => FileScanStatus::class,
            'purged_at' => 'datetime',
        ];
    }
}
