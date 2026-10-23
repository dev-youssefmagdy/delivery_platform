<?php

namespace App\Enums\System;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * One row of the admin integration health page. `isAdapter()` areas are external providers reached through app/Integrations.
 */
enum HealthArea: string implements HasLabel
{
    use EnumHelpers;

    case Database = 'database';
    case Redis = 'redis';
    case Queue = 'queue';
    case FailedJobs = 'failed_jobs';
    case Scheduler = 'scheduler';
    case Reverb = 'reverb';
    case Storage = 'storage';
    case Maps = 'maps';
    case Sms = 'sms';
    case Payments = 'payments';
    case Push = 'push';
    case FileScanner = 'file_scanner';
    case Email = 'email';
    case ErrorTracker = 'error_tracker';
    case PaymentWebhooks = 'payment_webhooks';

    public function label(): string
    {
        return __("system.health.areas.{$this->value}");
    }

    public function isAdapter(): bool
    {
        return in_array($this, [self::Maps, self::Sms, self::Payments, self::Push, self::FileScanner, self::Email, self::ErrorTracker], true);
    }

    /**
     * The config key under `integrations.` for adapter areas.
     */
    public function integrationKey(): ?string
    {
        return $this->isAdapter() ? $this->value : null;
    }
}
