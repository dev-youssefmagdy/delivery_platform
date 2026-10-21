<?php

namespace App\Http\Resources\Api\V1\Offer;

use App\Models\ProviderProfile;
use App\Models\Vehicle;
use Illuminate\Support\Facades\URL;

/**
 * What a customer may know about a provider and the vehicle on an offer card, before and after selection (SRS §6.5):
 * first name and last initial, photo through a short-lived signed URL, rating, completed jobs and the vehicle. No
 * phone, no document data, no exact position.
 */
final class ProviderIdentity
{
    /**
     * @return array<string, mixed>
     */
    public static function provider(ProviderProfile $profile): array
    {
        $user = $profile->user;

        return [
            'id' => $profile->uuid,
            'display_name' => self::displayName($user->name),
            'photo_url' => $user->photo_path === null ? null : URL::temporarySignedRoute(
                'api.v1.media.user-photo',
                now()->addMinutes((int) config('identity.photo.url_ttl_minutes', 15)),
                ['user' => $user->uuid, 'v' => substr(sha1($user->photo_path), 0, 12)],
            ),
            'rating' => ['average' => round((float) $profile->rating_avg, 2), 'count' => $profile->rating_count],
            'completed_jobs' => $profile->completed_jobs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function vehicle(Vehicle $vehicle, bool $showPlate): array
    {
        return [
            'category' => $vehicle->vehicleCategory->name,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'color' => $vehicle->color,
            'plate' => $showPlate ? $vehicle->plate_display : null,
            'capabilities' => array_values($vehicle->capabilities),
            'passenger_capacity' => $vehicle->passenger_capacity,
        ];
    }

    private static function displayName(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $parts = preg_split('/\s+/u', $name) ?: [$name];

        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0].' '.mb_substr((string) end($parts), 0, 1).'.';
    }
}
