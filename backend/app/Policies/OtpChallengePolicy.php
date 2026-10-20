<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Read-only OTP challenge log (otp_challenges.view). No codes are stored, so none can be shown.
 */
class OtpChallengePolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::OtpChallengesView->value);
    }
}
