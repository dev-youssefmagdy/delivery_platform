<?php

namespace App\Integrations\Sms\Contracts;

use App\Integrations\Sms\Exceptions\SmsDeliveryException;

/**
 * Sends one text message. The only way the platform reaches an SMS provider (ADR-12).
 */
interface SmsGateway
{
    /**
     * @param string $toE164 destination in E.164 format (`+201001234567`)
     *
     * @throws SmsDeliveryException when the provider did not accept the message
     */
    public function send(string $toE164, string $message): void;

    /**
     * Short driver name stored with the OTP challenge (`log`, `smsmisr`).
     */
    public function name(): string;
}
