<?php

namespace App\Services\Dispatch;

use App\DTOs\Dispatch\DispatchableRequest;
use App\DTOs\Dispatch\DispatchWaveResult;
use App\DTOs\Dispatch\EligibleProvider;
use App\Enums\Settings\SettingKey;
use App\Events\Realtime\BookingRequested;
use App\Events\Realtime\BookingSearchExpanding;
use App\Jobs\Dispatch\DispatchBookingWave;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Services\Catalog\CategoryPolicyResolver;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;

/**
 * Dispatch in controlled waves (SRS §10.2). Wave N searches the Nth radius step of the category policy (the last step
 * repeats), notifies at most one batch of new providers on their private channels and records each in
 * `booking_dispatch_recipients`; the next wave is a delayed job that only exists while the search window is open.
 * Nothing here claims that every driver was reached: events carry the wave and radius, never a count of "all".
 */
final class DispatchService
{
    public function __construct(
        private readonly FindEligibleProvidersService $finder,
        private readonly DispatchRecipientRepositoryInterface $recipients,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly CategoryPolicyResolver $policies,
        private readonly SettingsService $settings,
        private readonly EventSequencer $sequencer,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Starts dispatching: wave 1 runs on the critical queue.
     */
    public function start(DispatchableRequest $request): void
    {
        DispatchBookingWave::dispatch($request->bookingId, 1);
    }

    public function runWave(DispatchableRequest $request, int $wave): DispatchWaveResult
    {
        $now = CarbonImmutable::now();
        $category = $this->categories->find($request->serviceCategoryId);

        if (! $request->isOpenAt($now) || ! $category instanceof ServiceCategory) {
            return new DispatchWaveResult($wave, 0.0, 0, false);
        }

        $policy = $this->policies->resolve($category);
        $steps = $policy->dispatchRadiusStepsKm;
        $radiusKm = (float) $steps[min($wave, count($steps)) - 1];

        $found = $this->finder->find($request, $radiusKm, $policy->dispatchBatchSize);

        $notified = $this->db->transaction(function () use ($request, $wave, $found): array {
            $newIds = $this->recipients->recordWave($request->bookingId, $wave, array_map(static fn (EligibleProvider $provider): array => [
                'provider_profile_id' => $provider->profile->id,
                'distance_m' => $provider->distanceMeters,
            ], $found));

            return array_values(array_filter($found, static fn (EligibleProvider $provider): bool => in_array($provider->profile->id, $newIds, true)));
        });

        foreach ($notified as $provider) {
            BookingRequested::dispatch($provider->profile->uuid, $request->bookingUuid, $this->sequencer->next($request->bookingUuid), [
                'wave' => $wave,
                'distance_m' => $provider->distanceMeters,
                'pickup_area' => ['lat' => round($request->pickup->lat, 2), 'lng' => round($request->pickup->lng, 2)],
                'search_expires_at' => $request->searchExpiresAt->toIso8601String(),
                'request' => $request->summary,
            ]);
        }

        if ($wave > 1) {
            BookingSearchExpanding::dispatch($request->bookingUuid, $this->sequencer->next($request->bookingUuid), [
                'wave' => $wave,
                'radius_km' => $radiusKm,
            ]);
        }

        $interval = $this->settings->int(SettingKey::DispatchWaveIntervalSeconds);
        $scheduled = $request->searchExpiresAt->greaterThan($now->addSeconds($interval));

        if ($scheduled) {
            DispatchBookingWave::dispatch($request->bookingId, $wave + 1)->delay($interval);
        }

        return new DispatchWaveResult($wave, $radiusKm, count($notified), $scheduled);
    }
}
