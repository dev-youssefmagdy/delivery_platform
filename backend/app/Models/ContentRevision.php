<?php

namespace App\Models;

use App\Enums\Content\ContentType;
use App\Enums\Content\ContentWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Immutable snapshot of a content entity taken when it is saved or its status changes.
 *
 * @property int $id
 * @property ContentType $revisionable_type
 * @property int $revisionable_id
 * @property int $number
 * @property string $action
 * @property ContentWorkflowStatus $status
 * @property array<string, mixed> $snapshot
 * @property string|null $reason
 * @property int|null $admin_id
 * @property Carbon $created_at
 * @property-read Admin|null $admin
 */
class ContentRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'content_revisions';

    /** @var list<string> */
    protected $fillable = ['revisionable_type', 'revisionable_id', 'number', 'action', 'status', 'snapshot', 'reason', 'admin_id', 'created_at'];

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'revisionable_type' => ContentType::class,
            'status' => ContentWorkflowStatus::class,
            'snapshot' => 'array',
        ];
    }
}
