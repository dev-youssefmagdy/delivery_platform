<?php

namespace Tests\Feature\Support;

use App\Exceptions\ConflictException;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Route files and middleware groups (ADR-01, Plan §4.4).
 */
class RoutingTest extends TestCase
{
    public function test_api_v1_routes_are_prefixed_and_named(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.health');

        $this->assertNotNull($route);
        $this->assertSame('api/v1/health', $route->uri());
        $this->assertContains('api', $route->gatherMiddleware());
    }

    public function test_admin_group_uses_web_and_precognition_with_configurable_prefix(): void
    {
        Route::middleware(['web', HandlePrecognitiveRequests::class])
            ->prefix(config('admin.path'))
            ->name('admin.')
            ->get('_probe', fn () => 'ok')
            ->name('probe');

        $this->assertSame('admin', config('admin.path'));
        $this->get('/admin/_probe')->assertOk();
    }

    public function test_website_home_renders(): void
    {
        $this->get('/')->assertOk();
        foreach (['site.ar.home', 'site.en.home'] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name), $name);
        }
    }

    public function test_every_response_carries_a_request_id(): void
    {
        $this->get('/')->assertHeader('X-Request-Id');
        $this->getJson('/api/v1/health')->assertHeader('X-Request-Id');
    }

    public function test_domain_exceptions_on_html_routes_render_the_status_page_not_json(): void
    {
        Route::middleware('web')->get('_test/conflict', fn () => throw ConflictException::make('stale_version'));

        $response = $this->get('/_test/conflict', ['Accept' => 'text/html']);

        $response->assertStatus(409);
        $this->assertStringNotContainsString('"success"', (string) $response->getContent());
    }
}
