<?php

namespace App\Services\Scope;

use App\Models\Booking;
use App\Models\Vehicle;

/**
 * Does a vehicle carry the load of a request (SRS §10.1 "capacity", §5.1-E)? The vehicle's own declared payload / volume
 * wins; when it declares none the vehicle category's maximum applies; when neither is known the load cannot be judged
 * and the vehicle passes (the category's own limits were already checked at booking time).
 */
final class VehicleCapacityChecker
{
    /**
     * @return list<string> the dimensions that do not fit (`payload`, `volume`); empty when the vehicle fits
     */
    public function shortfalls(Vehicle $vehicle, ?float $payloadKg, ?float $volumeM3): array
    {
        $vehicle->loadMissing('vehicleCategory');
        $category = $vehicle->vehicleCategory;
        $maxPayload = $vehicle->payload_kg ?? $category->max_payload_kg;
        $maxVolume = $vehicle->volume_m3 ?? $category->max_volume_m3;
        $short = [];

        if ($payloadKg !== null && $maxPayload !== null && (float) $maxPayload > 0 && $payloadKg > (float) $maxPayload) {
            $short[] = 'payload';
        }

        if ($volumeM3 !== null && $maxVolume !== null && (float) $maxVolume > 0 && $volumeM3 > (float) $maxVolume) {
            $short[] = 'volume';
        }

        return $short;
    }

    public function fits(Vehicle $vehicle, ?float $payloadKg, ?float $volumeM3): bool
    {
        return $this->shortfalls($vehicle, $payloadKg, $volumeM3) === [];
    }

    /**
     * @return list<string>
     */
    public function shortfallsForBooking(Vehicle $vehicle, Booking $booking): array
    {
        return $this->shortfalls(
            $vehicle,
            $booking->cargo_weight_kg === null ? null : (float) $booking->cargo_weight_kg,
            $booking->cargo_volume_m3 === null ? null : (float) $booking->cargo_volume_m3,
        );
    }
}
