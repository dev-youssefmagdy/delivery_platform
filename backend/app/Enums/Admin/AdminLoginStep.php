<?php

namespace App\Enums\Admin;

/**
 * Where the login flow stands after a credentials check.
 */
enum AdminLoginStep: string
{
    /** Signed in. */
    case Authenticated = 'authenticated';

    /** Credentials accepted; the TOTP/recovery code is still required (not signed in yet). */
    case MfaChallenge = 'mfa_challenge';
}
