<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\DTOs\Provider\VehicleInput;
use App\Enums\Provider\VehicleCapability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /provider/vehicles.
 */
class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_category_id' => ['required', 'integer', 'min:1'],
            'make' => ['required', 'string', 'min:2', 'max:60'],
            'model' => ['required', 'string', 'min:1', 'max:60'],
            'year' => ['required', 'integer', 'min:1900', 'max:2200'],
            'color' => ['required', 'string', 'min:2', 'max:40'],
            'plate' => ['required', 'string', 'min:3', 'max:20'],
            'passenger_capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'payload_kg' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'volume_m3' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'capabilities' => ['sometimes', 'array', 'max:20'],
            'capabilities.*' => ['string', Rule::enum(VehicleCapability::class)],
        ];
    }

    public function toDto(): VehicleInput
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $text = static fn (string $key): ?string => isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : null;
        $int = static fn (string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;

        return new VehicleInput(
            vehicleCategoryId: $int('vehicle_category_id'),
            make: $text('make'),
            model: $text('model'),
            year: $int('year'),
            color: $text('color'),
            plate: $text('plate'),
            passengerCapacity: $int('passenger_capacity'),
            payloadKg: $text('payload_kg'),
            volumeM3: $text('volume_m3'),
            capabilities: array_key_exists('capabilities', $data) ? array_values(array_map('strval', (array) $data['capabilities'])) : null,
        );
    }
}
