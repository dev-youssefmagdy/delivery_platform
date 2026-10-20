<?php

namespace App\Models;

use App\Enums\Content\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A category's form schema. Exactly one `draft` row (version null) per category is edited in place; publishing
 * the category copies it into an immutable `published` row with the next version number (older ones are archived
 * by status only; their `fields` never change).
 *
 * @property int $id
 * @property int $service_category_id
 * @property int|null $version
 * @property list<array<string, mixed>> $fields canonical field definitions (see FormFieldDefinition)
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ServiceCategory $category
 */
class ServiceFormSchema extends Model
{
    protected $fillable = ['service_category_id', 'version', 'fields', 'status', 'published_at', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'fields' => '[]',
        'status' => 'draft',
        'lock_version' => 0,
    ];

    protected static function booted(): void
    {
        static::updating(static function (self $schema): void {
            $original = ContentStatus::tryFrom((string) $schema->getRawOriginal('status'));

            if ($original !== ContentStatus::Draft && $schema->isDirty(['fields', 'version', 'service_category_id', 'published_at'])) {
                throw new LogicException('A published form schema is immutable.');
            }
        });

        static::deleting(static function (self $schema): void {
            if ($schema->status !== ContentStatus::Draft) {
                throw new LogicException('A published form schema cannot be deleted.');
            }
        });
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function isDraft(): bool
    {
        return $this->status === ContentStatus::Draft;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'fields' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
