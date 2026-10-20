<?php

namespace App\Repositories\Eloquent\Provider;

use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Repositories\Contracts\Provider\VehiclePhotoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class VehiclePhotoRepository implements VehiclePhotoRepositoryInterface
{
    public function forVehicle(Vehicle $vehicle): Collection
    {
        return VehiclePhoto::query()->where('vehicle_id', $vehicle->id)->orderBy('id')->get();
    }

    public function countFor(Vehicle $vehicle): int
    {
        return VehiclePhoto::query()->where('vehicle_id', $vehicle->id)->count();
    }

    public function findByUuid(string $uuid): ?VehiclePhoto
    {
        return VehiclePhoto::query()->with('vehicle.providerProfile')->where('uuid', $uuid)->first();
    }

    public function create(array $attributes): VehiclePhoto
    {
        return VehiclePhoto::query()->create($attributes);
    }

    public function delete(VehiclePhoto $photo): void
    {
        $photo->delete();
    }
}
