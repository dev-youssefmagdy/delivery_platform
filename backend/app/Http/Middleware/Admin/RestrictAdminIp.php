<?php

namespace App\Http\Middleware\Admin;

use App\Enums\Settings\SettingKey;
use App\Services\Settings\SettingsService;
use App\Support\Admin\AdminIpAllowlist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional admin IP allowlist (typed setting `admin_ip_allowlist`, default empty = off). When set, every admin route
 * (login included) answers a bare 403 to any other address. The client IP is `$request->ip()`, so behind a load balancer the
 * trusted proxies must be configured or every visitor looks like the proxy.
 */
final class RestrictAdminIp
{
    public function __construct(private readonly SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $entries = AdminIpAllowlist::parse($this->settings->string(SettingKey::AdminIpAllowlist));

        if ($entries !== [] && ! AdminIpAllowlist::allows($request->ip(), $entries)) {
            abort(403);
        }

        return $next($request);
    }
}
