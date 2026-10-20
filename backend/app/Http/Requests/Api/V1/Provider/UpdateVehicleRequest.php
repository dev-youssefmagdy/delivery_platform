<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\Enums\Provider\VehicleCapability;
use Illuminate\Validation\Rule;

/**
 * PATCH /provider/vehicles/{vehicle}: any subset of the vehicle fields.
 */
class UpdateVehicleRequest extends StoreVehicleRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_category_id' => ['sometimes', 'integer', 'min:1'],
            'make' => ['sometimes', 'string', 'min:2', 'max:60'],
            'model' => ['sometimes', 'string', 'min:1', 'max:60'],
            'year' => ['sometimes', 'integer', 'min:1900', 'max:2200'],
            'color' => ['sometimes', 'string', 'min:2', 'max:40'],
            'plate' => ['sometimes', 'string', 'min:3', 'max:20'],
            'passenger_capacity' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'payload_kg' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:1000000'],
            'volume_m3' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:100000'],
            'capabilities' => ['sometimes', 'array', 'max:20'],
            'capabilities.*' => ['string', Rule::enum(VehicleCapability::class)],
        ];
    }
}
