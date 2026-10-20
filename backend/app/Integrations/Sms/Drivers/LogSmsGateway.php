<?php

namespace App\Integrations\Sms\Drivers;

use App\Integrations\Sms\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * Local/testing driver: writes the message to the `otp_dev` log channel, which is the ONLY place an OTP is ever
 * written (ADR-61). Refused at boot in any other environment by IntegrationGuard (`log` is a non-production driver).
 */
final class LogSmsGateway implements SmsGateway
{
    public function send(string $toE164, string $message): void
    {
        Log::channel('otp_dev')->debug('SMS to '.$toE164.': '.$message);
    }

    public function name(): string
    {
        return 'log';
    }
}
