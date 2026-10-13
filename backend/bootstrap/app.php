<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Exceptions\DomainException;
use App\Http\Middleware\CorrelationId;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\Idempotency;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

            Route::middleware(['web', HandlePrecognitiveRequests::class])
                ->prefix(config('admin.path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CorrelationId::class);

        $middleware->api(prepend: [ForceJsonResponse::class], append: [SetLocale::class]);
        $middleware->web(append: [SetLocale::class]);
        $middleware->throttleApi();

        $middleware->alias([
            'idempotent' => Idempotency::class,
            'locale' => SetLocale::class,
        ]);

        // Locale (user preference) and idempotency (actor scope) need the authenticated user.
        $middleware->appendToPriorityList(Authenticate::class, SetLocale::class);
        $middleware->appendToPriorityList(Authenticate::class, Idempotency::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([DomainException::class]);
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => ApiExceptionRenderer::wantsEnvelope($request));
        $exceptions->render(new ApiExceptionRenderer);
    })->create();
