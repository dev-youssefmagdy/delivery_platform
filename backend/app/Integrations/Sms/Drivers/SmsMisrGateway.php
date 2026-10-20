<?php

namespace App\Integrations\Sms\Drivers;

use App\Integrations\Sms\Contracts\SmsGateway;
use App\Integrations\Sms\Exceptions\SmsDeliveryException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

/**
 * SMS Misr (smsmisr.com), a widely used Egyptian SMS aggregator: `POST https://smsmisr.com/api/SMS` with the
 * credentials, sender token and message as query parameters; JSON answer `{"code":"1901","message":"Success",...}`
 * where `1901` means accepted.
 *
 * IMPLEMENTED FROM THE VENDOR DOCUMENTATION AND NOT TESTED AGAINST THE LIVE SERVICE (no credentials in this
 * repository). Verify the sender token, the unicode `language` flag and the error codes with a staging account
 * before go-live (docs/INTEGRATIONS.md). Credentials come from the environment only and are never logged.
 */
final class SmsMisrGateway implements SmsGateway
{
    private const ENDPOINT = 'https://smsmisr.com/api/SMS';

    private const SUCCESS_CODE = '1901';

    /** Language flag 3 = unicode text (Arabic). */
    private const LANGUAGE_UNICODE = 3;

    public function __construct(private readonly HttpFactory $http) {}

    public function send(string $toE164, string $message): void
    {
        $username = (string) config('services.smsmisr.username');
        $password = (string) config('services.smsmisr.password');
        $sender = (string) config('services.smsmisr.sender');

        if ($username === '' || $password === '' || $sender === '') {
            throw new SmsDeliveryException('SMS Misr credentials are not configured.');
        }

        try {
            $response = $this->http->timeout(10)->retry(1, 200, throw: false)->asForm()->post(self::ENDPOINT, [
                'environment' => (int) config('services.smsmisr.environment', 1),
                'username' => $username,
                'password' => $password,
                'sender' => $sender,
                'mobile' => ltrim($toE164, '+'),
                'language' => self::LANGUAGE_UNICODE,
                'message' => $message,
            ]);
        } catch (Throwable) {
            // Deliberately drop the original exception: its message can contain the request URL or body.
            throw new SmsDeliveryException('SMS Misr could not be reached.');
        }

        if (! $response->successful() || (string) $response->json('code') !== self::SUCCESS_CODE) {
            throw new SmsDeliveryException('SMS Misr rejected the message (code '.(string) $response->json('code').').');
        }
    }

    public function name(): string
    {
        return 'smsmisr';
    }
}
