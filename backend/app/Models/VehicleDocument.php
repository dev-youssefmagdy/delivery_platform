<?php

namespace App\Models;

use Database\Factories\VehicleDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document about one vehicle (registration, insurance, inspection certificate ...).
 *
 * @property int $vehicle_id
 * @property-read Vehicle $vehicle
 */
class VehicleDocument extends OwnedDocument
{
    /** @use HasFactory<VehicleDocumentFactory> */
    use HasFactory;

    protected $table = 'vehicle_documents';

    public function ownerProfile(): ProviderProfile
    {
        return $this->vehicle->providerProfile;
    }

    public function auditTarget(): Model
    {
        return $this->vehicle;
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
