<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * GET /api/v1/health (SRS §23.2): statuses only, no internal details.
 */
class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_ok_when_all_dependencies_and_the_scheduler_are_healthy(): void
    {
        $this->artisan('system:heartbeat')->assertSuccessful();

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJsonStructure([
                'success', 'message', 'data' => ['status', 'checks' => ['database', 'redis', 'queue', 'scheduler']], 'meta', 'errors',
            ])
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks', ['database' => 'ok', 'redis' => 'ok', 'queue' => 'ok', 'scheduler' => 'ok']);
    }

    public function test_missing_heartbeat_returns_503_without_details(): void
    {
        Cache::forget((string) config('health.heartbeat_cache_key'));

        $response = $this->getJson('/api/v1/health')
            ->assertStatus(503)
            ->assertJsonPath('errors._code', 'service_unavailable')
            ->assertJsonPath('meta.checks.scheduler', 'failing')
            ->assertJsonPath('meta.status', 'failing');

        $this->assertStringNotContainsString('heartbeat_age', (string) $response->getContent());
        $this->assertStringNotContainsString('duration_ms', (string) $response->getContent());
    }

    public function test_stale_heartbeat_is_failing(): void
    {
        $this->artisan('system:heartbeat')->assertSuccessful();
        $this->travel(10)->minutes();

        $this->getJson('/api/v1/health')->assertStatus(503)->assertJsonPath('meta.checks.scheduler', 'failing');
    }

    public function test_heartbeat_command_writes_the_current_timestamp_and_is_scheduled(): void
    {
        $this->freezeSecond();
        $this->artisan('system:heartbeat')->assertSuccessful();

        $this->assertSame(now()->getTimestamp(), Cache::get((string) config('health.heartbeat_cache_key')));

        $this->artisan('schedule:list')->expectsOutputToContain('system:heartbeat')->assertSuccessful();
    }
}
