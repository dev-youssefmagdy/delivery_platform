<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * Immutable snapshot of a category (configuration, resolved policies, vehicle categories and form schema) taken
 * at publish time. Bookings reference these rows (P2.3), so a row can never be updated or deleted.
 *
 * @property int $id
 * @property string $uuid
 * @property int $service_category_id
 * @property int $version
 * @property int $form_schema_id
 * @property array<string, mixed> $snapshot
 * @property string $reason
 * @property int|null $published_by_admin_id
 * @property Carbon $created_at
 * @property-read ServiceCategory $category
 * @property-read Admin|null $publisher
 * @property-read ServiceFormSchema $formSchema
 */
class ServiceCategoryVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['uuid', 'service_category_id', 'version', 'form_schema_id', 'snapshot', 'reason', 'published_by_admin_id'];

    protected static function booted(): void
    {
        static::creating(static function (self $version): void {
            $version->uuid ??= (string) Str::uuid();
        });

        static::updating(static function (): void {
            throw new LogicException('A category version is immutable.');
        });

        static::deleting(static function (): void {
            throw new LogicException('A category version cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'published_by_admin_id');
    }

    /**
     * @return BelongsTo<ServiceFormSchema, $this>
     */
    public function formSchema(): BelongsTo
    {
        return $this->belongsTo(ServiceFormSchema::class, 'form_schema_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
        ];
    }
}
