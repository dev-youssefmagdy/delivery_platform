<?php

namespace Tests\Support;

use App\Integrations\Sms\Contracts\SmsGateway;

/**
 * In-memory SMS gateway for tests: keeps the sent messages so a test can read the OTP.
 */
final class ArraySmsGateway implements SmsGateway
{
    /** @var list<array{to: string, message: string}> */
    public array $sent = [];

    public bool $fail = false;

    public function send(string $toE164, string $message): void
    {
        if ($this->fail) {
            throw new \App\Integrations\Sms\Exceptions\SmsDeliveryException('down');
        }

        $this->sent[] = ['to' => $toE164, 'message' => $message];
    }

    public function name(): string
    {
        return 'array';
    }

    public function lastCode(): string
    {
        preg_match('/\d{4,8}/', (string) end($this->sent)['message'], $m);

        return $m[0];
    }
}
