<?php

namespace App\Models;

use App\Enums\Admin\AdminExportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A queued CSV export requested from an admin listing (Plan §5.4).
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string $table
 * @property string|null $permission
 * @property array<string, mixed>|null $filters
 * @property AdminExportStatus $status
 * @property string $disk
 * @property string|null $path
 * @property string $filename
 * @property int $rows
 * @property string|null $error_code
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AdminExport extends Model
{
    use Prunable;

    protected $fillable = [
        'uuid', 'actor_type', 'actor_id', 'table', 'permission', 'filters', 'status', 'disk', 'path', 'filename',
        'rows', 'error_code', 'completed_at',
    ];

    /**
     * Exports older than the retention window are removed together with their file (hourly model:prune).
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays((int) config('admin.exports.retention_days', 7)));
    }

    protected function pruning(): void
    {
        if ($this->path !== null) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'filters' => 'array',
            'status' => AdminExportStatus::class,
            'rows' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
