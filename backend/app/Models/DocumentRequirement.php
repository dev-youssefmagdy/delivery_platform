<?php

namespace App\Models;

use App\Enums\Provider\ProviderType;
use App\Support\Audit\Auditable;
use Database\Factories\DocumentRequirementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Says that a document type applies (mandatory or optional) to providers/vehicles matching ALL of its non-null
 * scopes: country, city, service category, vehicle category, provider type. A row with no scope applies to everyone.
 *
 * @property int $id
 * @property int $document_type_id
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int|null $service_category_id
 * @property int|null $vehicle_category_id
 * @property ProviderType|null $provider_type
 * @property bool $mandatory
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read DocumentType $documentType
 */
class DocumentRequirement extends Model
{
    use Auditable;

    /** @use HasFactory<DocumentRequirementFactory> */
    use HasFactory;

    protected $fillable = ['document_type_id', 'country_id', 'city_id', 'service_category_id', 'vehicle_category_id', 'provider_type', 'mandatory'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['mandatory' => true];

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<VehicleCategory, $this>
     */
    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['provider_type' => ProviderType::class, 'mandatory' => 'boolean'];
    }
}
