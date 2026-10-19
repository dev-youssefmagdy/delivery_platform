<?php

namespace Tests\Feature\Admin;

use App\Enums\Admin\AdminPermission;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guard test: fails when an admin route is added without authorization.
 *
 * Every route named `admin.*` must be exactly one of:
 *  - guarded by `can:` (a policy ability or a permission name) on top of the authenticated admin group, or
 *  - explicitly marked `admin.access:self` (any signed-in admin acting on their own account / pre-authorized
 *    helpers such as lookups and exports), also inside the authenticated group, or
 *  - explicitly marked `admin.access:public` (login, password reset, logout, locale switch) and NOT behind the
 *    authenticated group.
 * Authenticated routes must also carry the session-tracking and MFA middleware.
 */
class AdminRouteAuthorizationGuardTest extends TestCase
{
    public function test_every_admin_route_is_authorized_or_explicitly_marked(): void
    {
        $violations = [];
        $inspected = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with((string) $route->getName(), 'admin.')) {
                continue;
            }

            $inspected++;

            foreach ($this->violations($route) as $violation) {
                $violations[] = $route->getName().' ['.implode('|', $route->methods()).' '.$route->uri().']: '.$violation;
            }
        }

        $this->assertGreaterThan(40, $inspected, 'The guard should see the whole admin route table.');
        $this->assertSame([], $violations, "Admin routes without proper authorization:\n".implode("\n", $violations));
    }

    public function test_the_guard_detects_an_admin_route_without_authorization(): void
    {
        Route::middleware(['web', 'auth:admin', 'admin.session', 'admin.mfa'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('__probe-unprotected', fn (): string => 'x')->name('probe.unprotected');
            Route::get('__probe-protected', fn (): string => 'x')->name('probe.protected')->can(AdminPermission::AdminsView->value);
        });

        Route::middleware(['web'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('__probe-open', fn (): string => 'x')->name('probe.open')->middleware('admin.access:self');
        });

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertNotSame([], $this->violations($routes->getByName('admin.probe.unprotected')));
        $this->assertSame([], $this->violations($routes->getByName('admin.probe.protected')));
        $this->assertNotSame([], $this->violations($routes->getByName('admin.probe.open')), 'a self route must be behind the authenticated group');
    }

    public function test_no_admin_route_exists_outside_the_admin_prefix_with_the_admin_name(): void
    {
        $prefix = (string) config('admin.path');

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (str_starts_with((string) $route->getName(), 'admin.')) {
                $this->assertTrue($route->uri() === $prefix || str_starts_with($route->uri(), $prefix.'/'), $route->getName().' is outside '.$prefix);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function violations(?LaravelRoute $route): array
    {
        if ($route === null) {
            return ['route not found'];
        }

        $middleware = array_map('strval', $route->gatherMiddleware());
        $has = static fn (string $needle): bool => collect($middleware)->contains(static fn (string $m): bool => $m === $needle || str_starts_with($m, $needle.':'));

        $authenticated = collect($middleware)->contains(static fn (string $m): bool => str_starts_with($m, Authenticate::class.':admin') || $m === 'auth:admin');
        $public = $has('admin.access') && collect($middleware)->contains(static fn (string $m): bool => $m === 'admin.access:public');
        $self = collect($middleware)->contains(static fn (string $m): bool => $m === 'admin.access:self');
        $authorized = collect($middleware)->contains(static fn (string $m): bool => str_starts_with($m, Authorize::class.':') || str_starts_with($m, 'can:'));

        $problems = [];

        if ($public) {
            if ($authenticated) {
                $problems[] = 'marked public but behind auth:admin';
            }

            return $problems;
        }

        if (! $authenticated) {
            $problems[] = 'not behind auth:admin (and not marked admin.access:public)';
        }

        if (! $authorized && ! $self) {
            $problems[] = 'no `can:` authorization and not marked admin.access:self';
        }

        if ($authorized && $self) {
            $problems[] = 'marked self but also has can: (pick one)';
        }

        if (! collect($middleware)->contains(static fn (string $m): bool => str_ends_with($m, 'EnsureAdminSession') || $m === 'admin.session')) {
            $problems[] = 'missing session tracking middleware';
        }

        if (! collect($middleware)->contains(static fn (string $m): bool => str_ends_with($m, 'EnforceAdminMfa') || $m === 'admin.mfa')) {
            $problems[] = 'missing MFA enforcement middleware';
        }

        return $problems;
    }
}
