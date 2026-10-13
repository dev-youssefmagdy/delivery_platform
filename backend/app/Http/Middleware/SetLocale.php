<?php

namespace App\Http\Middleware;

use App\Enums\Common\Locale;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale in this order (Plan §4.4):
 *   1. authenticated user's preference (HasLocalePreference),
 *   2. session `locale` (admin/website locale switcher; only when a session is started),
 *   3. `Accept-Language` best match among supported locales,
 *   4. default `ar`.
 * Sets the app, translator and Carbon locales and a `Content-Language` response header.
 */
final class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale->value);
        Carbon::setLocale($locale->value);
        CarbonImmutable::setLocale($locale->value);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale->value);

        return $response;
    }

    public function resolve(Request $request): Locale
    {
        $user = $request->user();

        if ($user instanceof HasLocalePreference && ($preferred = Locale::tryFromValue($user->preferredLocale())) !== null) {
            return $preferred;
        }

        if ($request->hasSession() && ($fromSession = Locale::tryFromValue($request->session()->get(self::SESSION_KEY))) !== null) {
            return $fromSession;
        }

        return $this->fromAcceptLanguage($request) ?? Locale::default();
    }

    private function fromAcceptLanguage(Request $request): ?Locale
    {
        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(substr(str_replace('-', '_', $language), 0, 2));

            if (($locale = Locale::tryFromValue($primary)) !== null) {
                return $locale;
            }
        }

        return null;
    }
}
