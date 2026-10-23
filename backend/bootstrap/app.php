<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Exceptions\DomainException;
use App\Http\Middleware\Admin\DeclareAdminAccess;
use App\Http\Middleware\Admin\EnforceAdminMfa;
use App\Http\Middleware\Admin\EnsureAdminSession;
use App\Http\Middleware\Admin\RestrictAdminIp;
use App\Http\Middleware\Admin\UseAdminGuard;
use App\Http\Middleware\CorrelationId;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\Idempotency;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TouchLastSeen;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/website.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api_v1.php'));

            Route::middleware(['web', RestrictAdminIp::class, HandlePrecognitiveRequests::class])
                ->prefix(config('admin.path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CorrelationId::class);

        // Behind a load balancer the client IP (admin allowlist, throttling, audit) and the scheme (secure cookies, HSTS) come from
        // the forwarded headers, but only from proxies listed in TRUSTED_PROXIES (comma separated IPs / CIDR, or `*`). Empty = none trusted.
        $trusted = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        $middleware->trustProxies(at: $trusted === ['*'] ? '*' : $trusted);

        $middleware->api(prepend: [ForceJsonResponse::class], append: [SetLocale::class, SecurityHeaders::class.':api']);
        $middleware->web(prepend: [UseAdminGuard::class], append: [SetLocale::class, SecurityHeaders::class.':auto']);
        $middleware->throttleApi();

        $middleware->alias([
            'idempotent' => Idempotency::class,
            'locale' => SetLocale::class,
            'admin.session' => EnsureAdminSession::class,
            'admin.mfa' => EnforceAdminMfa::class,
            'admin.access' => DeclareAdminAccess::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'account.active' => EnsureAccountActive::class,
            'user.seen' => TouchLastSeen::class,
        ]);

        // Signed-out admins go to the admin login; signed-in admins hitting a guest page go to the panel.
        $middleware->redirectGuestsTo(static fn (Request $request): string => route('admin.login'));
        $middleware->redirectUsersTo(static fn (Request $request): string => route('admin.home'));

        // Locale (user preference), idempotency (actor scope), admin session tracking and the MFA gate need the
        // authenticated user, so they run right after authentication and before route `can:` checks. The
        // default priority list names the AuthenticatesRequests contract, not the Authenticate class. Every insert
        // lands directly after it, so the last one listed runs first: session, MFA, locale, idempotency.
        $middleware->appendToPriorityList(AuthenticatesRequests::class, Idempotency::class);
        $middleware->appendToPriorityList(AuthenticatesRequests::class, SetLocale::class);
        $middleware->appendToPriorityList(AuthenticatesRequests::class, EnforceAdminMfa::class);
        $middleware->appendToPriorityList(AuthenticatesRequests::class, EnsureAdminSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([DomainException::class]);
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => ApiExceptionRenderer::wantsEnvelope($request));
        $exceptions->render(new ApiExceptionRenderer);
        // Optional error tracker (off unless SENTRY_DSN is set). Runs after the response, never changes logging or rendering.
        $exceptions->report(static function (Throwable $e): void {
            if (config('integrations.error_tracker.driver') === null) {
                return;
            }

            $requestId = Illuminate\Support\Facades\Context::get('request_id');
            app()->terminating(static function () use ($e, $requestId): void {
                try {
                    app(App\Integrations\Errors\Contracts\ErrorTracker::class)->report($e, is_string($requestId) ? $requestId : null);
                } catch (Throwable) {
                    // Error tracking must never fail a request.
                }
            });
        });
    })->create();
