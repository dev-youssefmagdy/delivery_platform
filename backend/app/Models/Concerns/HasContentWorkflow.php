<?php

namespace App\Models\Concerns;

use App\Enums\Content\ContentType;
use App\Enums\Content\ContentWorkflowStatus;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Shared by CMS entities that go through draft -> in_review -> scheduled/published -> archived.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * @property ContentWorkflowStatus $status
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int $lock_version
 */
trait HasContentWorkflow
{
    public static function bootHasContentWorkflow(): void
    {
        static::creating(static function ($model): void {
            if ($model->getAttribute('uuid') === null && in_array('uuid', $model->getFillable(), true)) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function contentType(): ContentType
    {
        return ContentType::forModel($this);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'published_by_admin_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), ContentWorkflowStatus::Published->value);
    }
}
