<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response security headers (P6.1, SRS §13, §20.7). One middleware, three profiles chosen by its parameter:
 *
 * - `auto` (the web group): `admin` under the admin path, `website` elsewhere.
 * - `admin`: CSP with `frame-ancestors 'none'`, scripts from the app only (`'unsafe-eval'` stays because the panel uses the standard
 *   Alpine build, see docs/SECURITY_REVIEW.md), map tiles from the configured tile host, `Referrer-Policy: no-referrer`.
 * - `website`: strict CSP, scripts only from the app or carrying the request nonce (no `unsafe-eval`, no inline handlers),
 *   `Referrer-Policy: strict-origin-when-cross-origin`.
 * - `api`: JSON only, so a locked-down CSP (`default-src 'none'`), `no-referrer`, and no caching of authenticated responses by
 *   shared caches (the controllers' own Cache-Control for the public catalogue endpoints is kept).
 *
 * Every profile sends nosniff, X-Frame-Options DENY, a Permissions-Policy and Cross-Origin-Opener-Policy; HSTS only over HTTPS and
 * outside local / testing. Headers a controller already set are not overwritten.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next, string $profile = 'auto'): Response
    {
        if ($profile === 'auto') {
            $profile = $request->is(trim((string) config('admin.path'), '/'), trim((string) config('admin.path'), '/').'/*') ? 'admin' : 'website';
        }

        $nonce = null;

        if ($profile === 'website') {
            $nonce = Str::random(24);
            Vite::useCspNonce($nonce);
            $request->attributes->set('csp_nonce', $nonce);
            view()->share('cspNonce', $nonce);
        }

        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Referrer-Policy' => $profile === 'website' ? 'strict-origin-when-cross-origin' : 'no-referrer',
        ];

        // CSP_MODE: `enforce` (default), `report-only` (the browser logs violations but blocks nothing, for a first rollout or a
        // suspected false positive) or `off`.
        $mode = (string) config('website.csp_mode', 'enforce');

        if ($mode !== 'off') {
            $headers[$mode === 'report-only' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy'] = $this->policy($profile, $nonce);
        }

        if ($request->isSecure() && ! app()->environment(['local', 'testing'])) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    private function policy(string $profile, ?string $nonce): string
    {
        if ($profile === 'api') {
            return "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'";
        }

        $dev = $this->devServer();
        $tiles = $profile === 'admin' ? $this->tileHost() : null;

        $script = $profile === 'admin' ? ["'self'", "'unsafe-eval'"] : ["'self'", "'nonce-{$nonce}'"];
        $img = ["'self'", 'data:', 'blob:'];
        $img = [...$img, ...array_values(array_filter((array) config('website.csp_img_hosts', []), 'is_string'))];
        $connect = ["'self'"];

        if ($tiles !== null) {
            $img[] = $tiles;
        }

        if ($profile === 'admin' && ($realtime = $this->realtimeOrigins()) !== []) {
            $connect = [...$connect, ...$realtime];
        }

        if ($dev !== null) {
            $script[] = $dev;
            $connect[] = $dev;
            $connect[] = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $dev);
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            "style-src 'self' 'unsafe-inline'".($dev !== null ? " {$dev}" : ''),
            'img-src '.implode(' ', $img),
            "font-src 'self' data:".($dev !== null ? " {$dev}" : ''),
            'connect-src '.implode(' ', $connect),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        return implode('; ', $directives);
    }

    /**
     * The Vite dev server origin while `public/hot` exists (local development only).
     */
    private function devServer(): ?string
    {
        if (! app()->environment(['local', 'testing']) || ! is_file(public_path('hot'))) {
            return null;
        }

        $url = trim((string) @file_get_contents(public_path('hot')));
        $parts = parse_url($url);

        return isset($parts['scheme'], $parts['host']) ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : null;
    }

    /**
     * The admin realtime (Reverb) origins, for the browser WebSocket, when configured.
     *
     * @return list<string>
     */
    private function realtimeOrigins(): array
    {
        $host = config('admin.realtime.host');

        if (! is_string($host) || $host === '') {
            return [];
        }

        $secure = config('admin.realtime.scheme') === 'https';
        $authority = $host.':'.(int) config('admin.realtime.port', $secure ? 443 : 8080);

        return [($secure ? 'wss://' : 'ws://').$authority];
    }

    private function tileHost(): ?string
    {
        $template = (string) config('admin.maps.tile_url', '');
        $parts = parse_url(str_replace(['{s}', '{z}', '{x}', '{y}', '{r}'], ['a', '0', '0', '0', ''], $template));

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        // Tile servers use a subdomain placeholder ({s}) and the browser asks for all of them.
        $host = str_contains($template, '{s}') ? '*.'.preg_replace('/^a\./', '', (string) $parts['host']) : $parts['host'];

        return $parts['scheme'].'://'.$host;
    }
}
