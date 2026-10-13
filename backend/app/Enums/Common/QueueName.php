<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;

/**
 * Named queues (Plan §4.4). Workers consume them in this priority order:
 * `php artisan queue:work redis --queue=critical,default,notifications,low`.
 */
enum QueueName: string
{
    use EnumHelpers;

    /** Expiry and dispatch jobs: latency sensitive. */
    case Critical = 'critical';
    case Default = 'default';
    case Notifications = 'notifications';
    case Low = 'low';

    public static function workerOrder(): string
    {
        return implode(',', self::values());
    }
}
