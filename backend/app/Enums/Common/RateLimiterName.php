<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;

/**
 * Named rate limiters registered in AppServiceProvider; limits are typed settings (SettingKey::RateLimit*) whose defaults live in config/rate_limits.php.
 * Use as route middleware: `throttle:`.RateLimiterName::OtpRequest->value.
 */
enum RateLimiterName: string
{
    use EnumHelpers;

    case Api = 'api';
    case OtpRequest = 'otp-request';
    case OtpVerify = 'otp-verify';
    case Location = 'location';
    case Offers = 'offers';
    case PublicForms = 'public-forms';
    case AdminLogin = 'admin-login';

    public function middleware(): string
    {
        return 'throttle:'.$this->value;
    }
}
