<?php

namespace Tests\Feature\Provider;

use App\Enums\Provider\EligibilityReason;
use App\Enums\Provider\EligibilityScope;
use App\Enums\Settings\SettingKey;
use App\Models\ProviderProfile;
use App\Services\Provider\ProviderEligibilityService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDispatch;
use Tests\TestCase;

/**
 * The runtime reasons of EligibilityScope::Working (location freshness, active job, cash debt limit) on top of the
 * onboarding ones, and proof that the onboarding scope does not ask for them.
 */
class WorkingEligibilityTest extends TestCase
{
    use BuildsDispatch;
    use RefreshDatabase;

    private function working(ProviderProfile $provider, $category = null): \App\DTOs\Provider\EligibilityResult
    {
        return app(ProviderEligibilityService::class)->check($provider->refresh(), $category, EligibilityScope::Working);
    }

    public function test_onboarding_scope_ignores_runtime_conditions(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);

        $this->assertTrue(app(ProviderEligibilityService::class)->check($provider, $category)->isEligible());
    }

    public function test_a_provider_without_a_position_has_a_location_reason(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);

        $result = $this->working($provider, $category);

        $this->assertSame([EligibilityReason::LocationStale], $result->reasons());
        $this->assertSame('missing', $result->issues[0]->reference);
    }

    public function test_a_fresh_position_is_enough_and_an_old_one_is_stale(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $last = app(\App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface::class);

        $last->upsert($provider, $this->sampleAt(), $city->id, now());
        $this->assertTrue($this->working($provider, $category)->isEligible());

        $stale = CarbonImmutable::now()->subSeconds(app(SettingsService::class)->int(SettingKey::LocationStaleAfterSeconds) + 5);
        $last->upsert($provider, $this->sampleAt(at: $stale), $city->id, now());
        $result = $this->working($provider, $category);

        $this->assertTrue($result->has(EligibilityReason::LocationStale));
        $this->assertSame('stale', $result->issues[0]->reference);
    }

    public function test_an_active_job_blocks_only_when_the_platform_allows_one_job_at_a_time(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        app(\App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface::class)->upsert($provider, $this->sampleAt(), $city->id, now());
        $this->fakeWorkload($this->busy());

        $this->assertTrue($this->working($provider, $category)->has(EligibilityReason::ActiveJobExists));

        app(SettingsService::class)->set(SettingKey::ProviderSingleActiveJob, false);
        $this->assertFalse($this->working($provider, $category)->has(EligibilityReason::ActiveJobExists));
    }

    public function test_cash_debt_over_the_limit_blocks_and_exactly_the_limit_does_not(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        app(\App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface::class)->upsert($provider, $this->sampleAt(), $city->id, now());
        $limit = app(SettingsService::class)->int(SettingKey::ProviderCashDebtLimitMinor);

        $this->fakeWorkload(null, $limit);
        $this->assertFalse($this->working($provider, $category)->has(EligibilityReason::CashDebtLimitExceeded));

        $this->fakeWorkload(null, $limit + 1);
        $this->assertTrue($this->working($provider, $category)->has(EligibilityReason::CashDebtLimitExceeded));
    }

    public function test_the_workload_reads_the_providers_assigned_booking_and_a_neutral_debt(): void
    {
        $repository = app(\App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface::class);
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);

        $this->assertNull($repository->activeBooking($provider));
        $this->assertSame(0, $repository->cashDebtMinor($provider));

        $booking = \App\Models\Booking::factory()->forCategory($category)->status(\App\Enums\Booking\BookingStatus::ProviderSelected)->state(['provider_id' => $provider->id])->create();
        $active = $repository->activeBooking($provider);

        $this->assertSame($booking->id, $active?->bookingId);
        $this->assertSame($booking->uuid, $active?->bookingUuid);
        $this->assertSame(\App\Enums\Location\TrackingState::ToPickup, $active?->trackingState);

        $booking->forceFill([])->save();
        \App\Support\Booking\BookingStatusGate::run(fn () => $booking->forceFill(['status' => \App\Enums\Booking\BookingStatus::InProgress])->save());
        $this->assertSame(\App\Enums\Location\TrackingState::Active, $repository->activeBooking($provider)?->trackingState);

        \App\Support\Booking\BookingStatusGate::run(fn () => $booking->forceFill(['status' => \App\Enums\Booking\BookingStatus::Completed])->save());
        $this->assertNull($repository->activeBooking($provider));
    }
}
