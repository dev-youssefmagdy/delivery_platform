<?php

namespace App\Integrations\Push\Drivers;

use App\Integrations\Push\Contracts\PushNotifier;
use App\Integrations\Push\Data\PushMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Local/testing driver: writes the push (never the full token) to the log. Refused at boot outside local/testing by
 * IntegrationGuard (`log` is a non-production driver); production without Firebase says "not configured" instead.
 */
final class LogPushNotifier implements PushNotifier
{
    public function send(PushMessage $message): string
    {
        $id = 'log-'.Str::uuid();

        Log::info('push (log driver, not delivered)', [
            'id' => $id,
            'token' => Str::mask($message->token, '*', 6),
            'title' => $message->title,
            'body' => $message->body,
            'data' => $message->data,
        ]);

        return $id;
    }

    public function name(): string
    {
        return 'log';
    }
}
