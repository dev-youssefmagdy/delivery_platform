<?php

namespace Tests\Feature\Location;

use App\Models\ProviderLocation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDispatch;
use Tests\TestCase;

class LocationRetentionTest extends TestCase
{
    use BuildsDispatch;
    use RefreshDatabase;

    private function row(int $providerId, int $daysAgo, ?int $bookingId = null): void
    {
        ProviderLocation::query()->create([
            'provider_profile_id' => $providerId,
            'point' => \App\Support\Geo\GeoPoint::make(30.05, 31.25),
            'source' => 'gps',
            'booking_id' => $bookingId,
            'recorded_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_history_is_pruned_by_the_two_retention_periods(): void
    {
        $provider = $this->makeProvider();
        $booking = \App\Models\Booking::factory()->create();
        $this->row($provider->id, 5);
        $this->row($provider->id, 31);
        $this->row($provider->id, 45, $booking->id);
        $this->row($provider->id, 91, $booking->id);

        $this->artisan('locations:prune')->assertSuccessful();

        $this->assertSame([5, 45], ProviderLocation::query()->orderBy('recorded_at', 'desc')->get()
            ->map(static fn ($row): int => (int) round(now()->diffInDays($row->recorded_at, true)))->all());
    }

    public function test_the_sweep_and_the_prune_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertTrue($events->contains(static fn ($event): bool => str_contains((string) $event->description, 'providers:sweep-stale')));
        $this->assertTrue($events->contains(static fn ($event): bool => str_contains($event->command ?? '', 'locations:prune')));
    }
}
