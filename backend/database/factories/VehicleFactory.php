<?php

namespace Database\Factories;

use App\Enums\Provider\VehicleStatus;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Support\Vehicles\PlateNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plate = fake()->unique()->bothify('? ? ? ####');

        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'vehicle_category_id' => VehicleCategory::factory(),
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'color' => 'White',
            'plate_display' => $plate,
            'plate_normalized' => PlateNumber::normalize($plate),
            'passenger_capacity' => 4,
            'capabilities' => [],
            'status' => VehicleStatus::Draft,
        ];
    }

    public function status(VehicleStatus $status): static
    {
        return $this->state(['status' => $status, 'submitted_at' => $status === VehicleStatus::Draft ? null : now()->subHour()]);
    }

    public function approved(): static
    {
        return $this->status(VehicleStatus::Approved);
    }
}
