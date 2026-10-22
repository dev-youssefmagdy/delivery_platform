<?php

namespace App\Services\Notification\Channels;

use App\Enums\Notification\NotificationChannel;
use App\Models\Admin;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notification\DeliveryOutcome;

/**
 * Sends one delivery on one channel. Never throws for provider problems: it answers with a `DeliveryOutcome` so the
 * processor can record, retry or give up.
 */
interface ChannelSender
{
    public function channel(): NotificationChannel;

    public function send(NotificationDelivery $delivery, User|Admin $recipient): DeliveryOutcome;
}
