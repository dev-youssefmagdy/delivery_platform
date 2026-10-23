<?php

namespace App\Support\Website;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Spam protection of the public forms without a third party: a hidden honeypot field (`website_url`, must stay empty) and a
 * time trap (a hidden encrypted render timestamp; a form submitted faster than a person can fill it, or hours later, is refused).
 * A captcha adapter can replace or add to this later; the forms only call `token()` and the `FormTimeTrap` rule.
 */
final class FormGuard
{
    public const HONEYPOT = 'website_url';

    public const TOKEN_FIELD = '_ft';

    public function token(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    /**
     * @return string|null null when fine, else a `site.forms.errors.*` key
     */
    public function check(mixed $token): ?string
    {
        if (! is_string($token) || $token === '') {
            return 'form_invalid';
        }

        try {
            $issued = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return 'form_invalid';
        }

        $age = now()->getTimestamp() - $issued;

        if ($age < (int) config('website.forms.min_fill_seconds', 3)) {
            return 'form_too_fast';
        }

        if ($age > (int) config('website.forms.max_age_minutes', 120) * 60) {
            return 'form_expired';
        }

        return null;
    }
}
