<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Enums\Identity\ClientApp;

/**
 * Who an event is addressed to; decides which app's devices and inbox receive it.
 */
enum NotificationAudience: string implements HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Provider = 'provider';
    case Both = 'both';
    case Admin = 'admin';

    public function allows(?ClientApp $app): bool
    {
        return match ($this) {
            self::Customer => $app === ClientApp::Customer,
            self::Provider => $app === ClientApp::Provider,
            self::Both => $app !== null,
            self::Admin => $app === null,
        };
    }

    public function label(): string
    {
        return __("enums.notification_audience.{$this->value}");
    }
}
