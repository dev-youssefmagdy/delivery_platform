<?php

namespace App\Repositories\Contracts\Provider;

use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use Illuminate\Database\Eloquent\Collection;

interface VehiclePhotoRepositoryInterface
{
    /**
     * @return Collection<int, VehiclePhoto>
     */
    public function forVehicle(Vehicle $vehicle): Collection;

    public function countFor(Vehicle $vehicle): int;

    public function findByUuid(string $uuid): ?VehiclePhoto;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): VehiclePhoto;

    public function delete(VehiclePhoto $photo): void;
}
