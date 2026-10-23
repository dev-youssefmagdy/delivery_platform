<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ArrivalData;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;

/**
 * Builds the GPS + time evidence of a provider-side event at the pickup: the arrival (P2.5) and, since P3.5, the report
 * of a customer no-show. Shared so both are judged by the same freshness, accuracy and radius rules.
 */
final class ArrivalEvidenceBuilder
{
    public function __construct(private readonly ProviderLastLocationRepositoryInterface $locations) {}

    /**
     * GPS evidence of an arrival: the fix the app sent, or the last known position when it sent none. `verified` means a
     * recent, accurate fix within the arrival radius of the pickup; anything else is stored as unverified and the
     * cancellation rules then treat the provider as "on the way" (SRS §8.4).
     *
     * @return array<string, mixed>
     */
    public function build(Booking $booking, ProviderProfile $provider, ArrivalData $arrival, CarbonImmutable $now): array
    {
        $point = null;
        $accuracy = null;
        $capturedAt = null;
        $source = 'none';

        if ($arrival->hasFix()) {
            $point = GeoPoint::tryMake($arrival->lat, $arrival->lng);
            $accuracy = $arrival->accuracyMeters;
            $capturedAt = $arrival->recordedAt ?? $now;
            $source = 'app';
        }

        $maxAge = (int) config('booking.trip.evidence_max_age_seconds', 120);

        if ($point === null) {
            $last = $this->locations->findFor($provider);

            if ($last !== null && $last->recorded_at->greaterThanOrEqualTo($now->subSeconds($maxAge))) {
                $point = $last->point;
                $accuracy = $last->accuracy_m;
                $capturedAt = CarbonImmutable::instance($last->recorded_at);
                $source = 'last_known';
            }
        }

        if ($point === null) {
            return ['source' => 'none', 'verified' => false, 'recorded_at' => $now->toIso8601ZuluString()];
        }

        $distance = (int) round($booking->pickup_point->distanceTo($point));
        $radius = (int) config('booking.trip.arrival_radius_m', 300);
        $fresh = $capturedAt->greaterThanOrEqualTo($now->subSeconds($maxAge)) && $capturedAt->lessThanOrEqualTo($now->addSeconds(60));

        return [
            'source' => $source,
            'lat' => round($point->lat, 6),
            'lng' => round($point->lng, 6),
            'accuracy_m' => $accuracy,
            'captured_at' => $capturedAt->toIso8601ZuluString(),
            'recorded_at' => $now->toIso8601ZuluString(),
            'distance_to_pickup_m' => $distance,
            'radius_m' => $radius,
            'verified' => $fresh && $distance <= $radius && ($accuracy === null || $accuracy <= max($radius, 100)),
        ];
    }
}
