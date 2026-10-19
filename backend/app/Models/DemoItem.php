<?php

namespace App\Models;

use App\Enums\Demo\DemoItemStatus;
use App\Enums\Demo\DemoItemTag;
use App\Enums\Demo\DemoItemType;
use App\Support\Audit\Auditable;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Database\Factories\DemoItemFactory;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * Proof-of-kit record (P0.3): exercises every admin form component. Local/testing only.
 *
 * @property int $id
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string|null $description translated in the current locale
 * @property string $code
 * @property DemoItemType $type
 * @property DemoItemStatus $status
 * @property Money $price
 * @property int $price_minor
 * @property string $currency
 * @property int|null $country_id
 * @property Collection<int, DemoItemTag>|null $tags
 * @property bool $is_featured
 * @property bool $is_public
 * @property Carbon|null $available_on
 * @property Carbon|null $published_at
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property string|null $cover_path
 * @property string|null $latitude
 * @property string|null $longitude
 * @property list<array{0: float, 1: float}>|null $area
 * @property array<string, string>|null $specs
 * @property list<array{label: string, price: int|null, is_default: bool}>|null $options
 * @property string|null $notes
 * @property string|null $archived_reason
 * @property Carbon|null $archived_at
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Country|null $country
 */
class DemoItem extends Model
{
    use Auditable;

    /** @use HasFactory<DemoItemFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name', 'description', 'code', 'type', 'status', 'price', 'price_minor', 'currency', 'country_id', 'tags',
        'is_featured', 'is_public', 'available_on', 'published_at', 'starts_on', 'ends_on', 'cover_path',
        'latitude', 'longitude', 'area', 'specs', 'options', 'notes', 'archived_reason', 'archived_at', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'is_featured' => false,
        'is_public' => false,
        'lock_version' => 0,
    ];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DemoItemType::class,
            'status' => DemoItemStatus::class,
            'price' => MoneyCast::class.':price_minor,currency',
            'price_minor' => 'integer',
            'tags' => AsEnumCollection::of(DemoItemTag::class),
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
            'available_on' => 'date',
            'published_at' => 'datetime',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'area' => 'array',
            'specs' => 'array',
            'options' => 'array',
            'archived_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
