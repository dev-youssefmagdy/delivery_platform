<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Services\Provider\DocumentAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A vehicle as its owner sees it (photos with signed URLs, status and the reviewer's rejection reason).
 *
 * @property Vehicle $resource
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vehicle = $this->resource;
        $access = app(DocumentAccessService::class);

        return [
            'id' => $vehicle->uuid,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'color' => $vehicle->color,
            'plate' => $vehicle->plate_display,
            'passenger_capacity' => $vehicle->passenger_capacity,
            'payload_kg' => $vehicle->payload_kg,
            'volume_m3' => $vehicle->volume_m3,
            'capabilities' => $vehicle->capabilities,
            'status' => $vehicle->status->value,
            'rejection_reason' => $vehicle->rejection_reason,
            'editable' => $vehicle->status->isEditableByProvider(),
            'photos' => $vehicle->relationLoaded('photos')
                ? $vehicle->photos->map(static fn (VehiclePhoto $photo): array => [
                    'id' => $photo->uuid,
                    'kind' => $photo->kind->value,
                    'url' => $access->ownerPhotoUrl($photo),
                ])->values()->all()
                : [],
            'submitted_at' => $vehicle->submitted_at?->toIso8601ZuluString(),
        ];
    }
}
