<?php

namespace App\Rules;

use App\Support\Admin\AdminIpAllowlist;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every entry is an IP address or CIDR range, and the list must include the address of the administrator saving it, so
 * the setting can never lock its own author out of the panel.
 */
final class AdminIpAllowlistRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $entries = AdminIpAllowlist::parse(is_string($value) ? $value : null);

        foreach ($entries as $entry) {
            if (! AdminIpAllowlist::isValidEntry($entry)) {
                $fail('admin.settings.errors.ip_allowlist_invalid')->translate(['entry' => $entry]);

                return;
            }
        }

        $ip = request()->ip();

        if ($entries !== [] && $ip !== null && ! AdminIpAllowlist::allows($ip, $entries)) {
            $fail('admin.settings.errors.ip_allowlist_lockout')->translate(['ip' => $ip]);
        }
    }
}
