<?php

namespace App\Models;

use App\Enums\Catalog\ServiceFamilyKind;
use App\Support\Audit\Auditable;
use Database\Factories\ServiceFamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A group of related service categories (passenger rides, courier, moving ...). SRS §5.1.
 *
 * @property int $id
 * @property string $code
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string|null $description translated in the current locale; raw JSON {ar, en}
 * @property ServiceFamilyKind $kind
 * @property string|null $icon
 * @property int $sort_order
 * @property bool $active
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ServiceFamily extends Model
{
    use Auditable;

    /** @use HasFactory<ServiceFamilyFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = ['code', 'name', 'description', 'kind', 'icon', 'sort_order', 'active', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'active' => false,
        'lock_version' => 0,
    ];

    /**
     * @return HasMany<ServiceCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class, 'service_family_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ServiceFamilyKind::class,
            'sort_order' => 'integer',
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
