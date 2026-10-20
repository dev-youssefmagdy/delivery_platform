<?php

namespace App\Listeners;

use App\Events\ProviderStatusChanged;
use App\Services\Provider\ProviderNotifier;

final class NotifyProviderOfStatusChange
{
    public function __construct(private readonly ProviderNotifier $notifier) {}

    public function handle(ProviderStatusChanged $event): void
    {
        $this->notifier->statusChanged($event->profile, $event->from, $event->to, $event->reason);
    }
}
