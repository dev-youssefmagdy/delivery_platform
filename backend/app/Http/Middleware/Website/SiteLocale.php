<?php

namespace App\Http\Middleware\Website;

use App\Enums\Common\Locale;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public website's language is part of the URL (Arabic unprefixed, English under /en), so search engines and shared links
 * always get the same language. Route middleware parameter: `site.locale:ar` / `site.locale:en`. Overrides the session /
 * Accept-Language resolution of SetLocale for these routes.
 */
final class SiteLocale
{
    public function handle(Request $request, Closure $next, string $locale = 'ar'): Response
    {
        $resolved = Locale::tryFromValue($locale) ?? Locale::default();

        app()->setLocale($resolved->value);
        Carbon::setLocale($resolved->value);
        CarbonImmutable::setLocale($resolved->value);

        return $next($request);
    }
}
