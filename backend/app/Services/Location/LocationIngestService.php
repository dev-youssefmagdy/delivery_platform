<?php

namespace App\Services\Location;

use App\DTOs\Location\LocationIngestResult;
use App\DTOs\Location\LocationSample;
use App\Enums\Location\LocationAnomaly;
use App\Enums\Location\TrackingState;
use App\Enums\Settings\SettingKey;
use App\Events\Realtime\TripLocationUpdated;
use App\Exceptions\ConflictException;
use App\Models\ProviderLastLocation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Location\ProviderLocationRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;
use App\Services\Dispatch\EventSequencer;
use App\Services\Geography\GeoService;
use App\Services\Settings\SettingsService;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Takes a batch of driver position samples (SRS §10.3). Each sample is checked against the last trusted position: one
 * that implies an impossible speed or carries a future timestamp is stored in the history with an anomaly flag (never
 * silently dropped) and does not move the provider; late or out-of-order samples go to the history only. The newest good
 * sample updates the last location, the geo index (while online) and, when a booking is being served, the booking's
 * private channel. The history keeps one sample per configured interval plus every flagged one.
 */
final class LocationIngestService
{
    public function __construct(
        private readonly ProviderLocationRepositoryInterface $history,
        private readonly ProviderLastLocationRepositoryInterface $last,
        private readonly ProviderWorkloadRepositoryInterface $workload,
        private readonly ProviderIndexPublisher $index,
        private readonly GeoService $geo,
        private readonly SettingsService $settings,
        private readonly EventSequencer $sequencer,
        private readonly BookingRepositoryInterface $bookings,
    ) {}

    /**
     * @param list<LocationSample> $samples
     *
     * @throws ConflictException `location_not_tracking` when the provider is neither online nor serving a booking
     */
    public function ingest(ProviderProfile $profile, array $samples, bool $requireTracking = true): LocationIngestResult
    {
        $active = $this->workload->activeBooking($profile);

        if ($requireTracking && ! $profile->is_online && $active === null) {
            throw ConflictException::make('location_not_tracking');
        }

        usort($samples, static fn (LocationSample $a, LocationSample $b): int => $a->recordedAt <=> $b->recordedAt);

        $now = CarbonImmutable::now();
        $reference = $this->last->findFor($profile);
        $referencePoint = $reference?->point;
        $referenceTime = $reference === null ? null : CarbonImmutable::instance($reference->recorded_at);
        $storedAt = $this->history->latestRecordedAt($profile);
        $interval = $this->settings->int(SettingKey::LocationHistoryIntervalSeconds);
        $maxSpeedMps = $this->settings->int(SettingKey::LocationMaxSpeedKmh) / 3.6;
        $maxAge = (int) config('location.max_sample_age_seconds');
        $skew = (int) config('location.clock_skew_seconds');

        $newest = null;
        $applied = 0;
        $flagged = 0;
        $stale = 0;

        foreach ($samples as $sample) {
            $anomaly = null;
            $isStale = false;

            if ($sample->recordedAt->greaterThan($now->addSeconds($skew))) {
                $anomaly = LocationAnomaly::FutureTimestamp;
            } elseif ($referenceTime !== null && $sample->recordedAt->lessThanOrEqualTo($referenceTime)) {
                $isStale = true;
            } elseif ($sample->recordedAt->lessThan($now->subSeconds($maxAge))) {
                $isStale = true;
            } elseif ($referencePoint !== null && $referenceTime !== null && $this->impliesImpossibleSpeed($referencePoint, $referenceTime, $sample, $maxSpeedMps)) {
                $anomaly = LocationAnomaly::ImpossibleSpeed;
            }

            $due = $storedAt === null || $sample->recordedAt->diffInSeconds($storedAt, true) >= $interval;

            if ($anomaly !== null || $due) {
                $this->history->append($profile, $sample, $anomaly, $active?->bookingId);
                $storedAt = $sample->recordedAt;
            }

            if ($anomaly !== null) {
                $flagged++;
                Log::warning('provider.location_anomaly', ['provider' => $profile->uuid, 'anomaly' => $anomaly->value]);

                continue;
            }

            if ($isStale) {
                $stale++;

                continue;
            }

            $applied++;
            $referencePoint = $sample->point;
            $referenceTime = $sample->recordedAt;
            $newest = $sample;
        }

        if ($newest !== null) {
            $stored = $this->last->upsert($profile, $newest, $this->resolveCityId($reference, $newest), $now);

            if ($profile->is_online) {
                $this->index->publish($profile, $stored);
            }

            if ($active !== null) {
                $this->broadcast($active->bookingUuid, $stored);
            }
        }

        return new LocationIngestResult(count($samples), $applied, $flagged, $stale, $this->trackingState($profile, $active?->trackingState));
    }

    public function trackingState(ProviderProfile $profile, ?TrackingState $active = null): TrackingState
    {
        $active ??= $this->workload->activeBooking($profile)?->trackingState;

        return $active ?? ($profile->is_online ? TrackingState::Idle : TrackingState::Off);
    }

    private function impliesImpossibleSpeed(GeoPoint $from, CarbonImmutable $fromTime, LocationSample $sample, float $maxSpeedMps): bool
    {
        $meters = $from->distanceTo($sample->point);

        // Ordinary GPS jitter between two close samples is not a jump.
        if ($meters < 100.0) {
            return false;
        }

        $seconds = max(1.0, $fromTime->diffInMilliseconds($sample->recordedAt, true) / 1000);

        return $meters / $seconds > $maxSpeedMps;
    }

    private function resolveCityId(?ProviderLastLocation $reference, LocationSample $sample): ?int
    {
        $recheck = (float) config('location.city_recheck_meters');

        if ($reference?->city_id !== null && $reference->point->distanceTo($sample->point) < $recheck) {
            return $reference->city_id;
        }

        return $this->geo->zonesAt($sample->point)->first()?->city_id;
    }

    private function broadcast(string $bookingUuid, ProviderLastLocation $location): void
    {
        $customerUuid = $this->bookings->customerUuidOfBooking($bookingUuid);

        if ($customerUuid === null) {
            return;
        }

        TripLocationUpdated::toCustomer($customerUuid, $bookingUuid, $this->sequencer->next($bookingUuid), [
            'lat' => round($location->point->lat, 6),
            'lng' => round($location->point->lng, 6),
            'heading' => $location->heading,
            'speed_mps' => $location->speed_mps,
            'accuracy_m' => $location->accuracy_m,
            'recorded_at' => CarbonImmutable::instance($location->recorded_at)->toIso8601String(),
        ]);
    }
}
