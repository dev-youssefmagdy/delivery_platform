<?php

namespace App\Services\Provider;

use App\DTOs\Location\LocationSample;
use App\DTOs\Provider\AvailabilityState;
use App\Enums\Location\OfflineReason;
use App\Enums\Provider\EligibilityScope;
use App\Enums\Settings\SettingKey;
use App\Events\Realtime\ProviderAvailabilityChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ProviderNotEligibleException;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Dispatch\EventSequencer;
use App\Services\Location\LocationIngestService;
use App\Services\Location\ProviderIndexPublisher;
use App\Services\Offers\ProviderOfferInvalidator;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

/**
 * Online / offline (SRS §7.2). Going online runs the single eligibility service with `EligibilityScope::Working`
 * (Plan I11), needs a fresh position inside a service zone, and fixes the vehicle the driver works with. Going offline
 * with a job in progress is refused unless the driver confirms: the job continues, only new requests stop. A scheduled
 * sweep takes drivers offline whose position went stale, and status changes / expiries use `forceOffline()`.
 */
final class AvailabilityService
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderLastLocationRepositoryInterface $locations,
        private readonly ProviderWorkloadRepositoryInterface $workload,
        private readonly ProviderEligibilityService $eligibility,
        private readonly LocationIngestService $ingest,
        private readonly ProviderIndexPublisher $index,
        private readonly ProviderOfferInvalidator $offerInvalidator,
        private readonly SettingsService $settings,
        private readonly EventSequencer $sequencer,
        private readonly DatabaseManager $db,
    ) {}

    public function stateOf(ProviderProfile $profile): AvailabilityState
    {
        return new AvailabilityState(
            online: $profile->is_online,
            onlineSince: $profile->online_since,
            vehicle: $profile->current_vehicle_id === null ? null : $this->vehicles->find($profile->current_vehicle_id),
            trackingState: $this->ingest->trackingState($profile),
            lastOfflineReason: $profile->last_offline_reason,
            staleAfterSeconds: $this->settings->int(SettingKey::LocationStaleAfterSeconds),
            activeBookingUuid: $this->workload->activeBooking($profile)?->bookingUuid,
        );
    }

    /**
     * @throws ProviderNotEligibleException 422 with typed reasons
     * @throws BusinessRuleViolation `vehicle_not_usable`, `outside_service_area`
     */
    public function goOnline(ProviderProfile $profile, ?string $vehicleUuid, LocationSample $sample): AvailabilityState
    {
        $state = $this->db->transaction(function () use ($profile, $vehicleUuid, $sample): ProviderProfile {
            $locked = $this->profiles->lockForUpdate($profile->id) ?? $profile;

            $this->ingest->ingest($locked, [$sample], requireTracking: false);
            $result = $this->eligibility->check($locked, null, EligibilityScope::Working);

            if (! $result->isEligible()) {
                throw ProviderNotEligibleException::because($result);
            }

            $vehicle = $this->chooseVehicle($locked, $vehicleUuid, $result->usableVehicleIds);
            $location = $this->locations->findFor($locked);

            if ($location === null || $location->city_id === null) {
                throw BusinessRuleViolation::make('outside_service_area');
            }

            $wasOnline = $locked->is_online;
            $this->profiles->setCurrentVehicle($locked, $vehicle->id);
            $this->profiles->setOnline($locked, true);
            $locked->refresh();
            $this->index->publish($locked, $location);

            if (! $wasOnline) {
                $this->announce($locked, true, null);
            }

            return $locked;
        });

        return $this->stateOf($state);
    }

    /**
     * @throws ConflictException `active_job_blocks_offline` when a job is in progress and `$confirm` is false
     */
    public function goOffline(ProviderProfile $profile, bool $confirm = false): AvailabilityState
    {
        $locked = $this->db->transaction(function () use ($profile, $confirm): ProviderProfile {
            $locked = $this->profiles->lockForUpdate($profile->id) ?? $profile;

            if ($this->workload->activeBooking($locked) !== null && ! $confirm) {
                throw ConflictException::make('active_job_blocks_offline');
            }

            $this->takeOffline($locked, OfflineReason::ProviderChoice);

            return $locked;
        });

        return $this->stateOf($locked);
    }

    /**
     * Stops availability regardless of any confirmation (suspension, expired document, stale position).
     */
    public function forceOffline(ProviderProfile $profile, OfflineReason $reason): void
    {
        $this->db->transaction(function () use ($profile, $reason): void {
            $this->takeOffline($this->profiles->lockForUpdate($profile->id) ?? $profile, $reason);
        });
    }

    /**
     * Scheduled sweep: providers whose position is older than the stale threshold are taken offline.
     *
     * @return int providers taken offline
     */
    public function sweepStale(int $limit = 500): int
    {
        $before = CarbonImmutable::now()->subSeconds($this->settings->int(SettingKey::LocationStaleAfterSeconds));
        $count = 0;

        foreach ($this->profiles->onlineWithLocationOlderThan($before, $limit) as $profile) {
            $this->forceOffline($profile, OfflineReason::StaleLocation);
            $count++;
        }

        if ($count > 0) {
            Log::info('provider.availability.stale_sweep', ['taken_offline' => $count]);
        }

        return $count;
    }

    private function takeOffline(ProviderProfile $profile, OfflineReason $reason): void
    {
        // The status service already clears the online flag when an account is suspended; the driver still has to be told.
        $notify = $profile->is_online || $reason === OfflineReason::AccountChanged;

        if ($notify) {
            $this->profiles->setOffline($profile, $reason);
        }

        $this->index->withdraw($profile);

        // A provider that left the index can no longer be chosen: its open offers end now (SRS §10.2 step 7).
        $this->offerInvalidator->invalidateFor($profile);

        if ($notify) {
            $this->announce($profile, false, $reason);
        }
    }

    private function announce(ProviderProfile $profile, bool $online, ?OfflineReason $reason): void
    {
        ProviderAvailabilityChanged::dispatch($profile->uuid, null, $this->sequencer->next('provider:'.$profile->uuid), [
            'online' => $online,
            'reason' => $reason?->value,
        ]);
    }

    /**
     * @param list<int> $usableVehicleIds
     */
    private function chooseVehicle(ProviderProfile $profile, ?string $vehicleUuid, array $usableVehicleIds): Vehicle
    {
        if ($vehicleUuid !== null) {
            $vehicle = $this->vehicles->findByUuid($vehicleUuid);

            if (! $vehicle instanceof Vehicle || $vehicle->provider_profile_id !== $profile->id || ! in_array($vehicle->id, $usableVehicleIds, true)) {
                throw BusinessRuleViolation::make('vehicle_not_usable', 'vehicle_id');
            }

            return $vehicle;
        }

        $preferred = in_array($profile->current_vehicle_id, $usableVehicleIds, true) ? $profile->current_vehicle_id : $usableVehicleIds[0];
        $vehicle = $this->vehicles->find($preferred);

        if (! $vehicle instanceof Vehicle) {
            throw BusinessRuleViolation::make('vehicle_not_usable', 'vehicle_id');
        }

        return $vehicle;
    }
}
