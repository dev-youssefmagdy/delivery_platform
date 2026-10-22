<?php

namespace App\Providers;

use App\Services\Notification\Channels\ChannelSender;
use App\Services\Notification\Channels\EmailChannelSender;
use App\Services\Notification\Channels\PushChannelSender;
use App\Services\Notification\Channels\SmsChannelSender;
use App\Services\Notification\DeliveryProcessor;
use App\Services\Notification\TemplateResolver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the notification platform: the channel senders the delivery processor can use.
 */
class NotificationServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string<ChannelSender>>
     */
    private const SENDERS = [PushChannelSender::class, SmsChannelSender::class, EmailChannelSender::class];

    public function register(): void
    {
        $this->app->when(TemplateResolver::class)->needs(Loader::class)->give(static fn (Container $app): Loader => $app->make('translator')->getLoader());

        $this->app->tag(self::SENDERS, 'notification.channel-senders');

        $this->app->bind(DeliveryProcessor::class, fn (Container $app): DeliveryProcessor => new DeliveryProcessor(
            $app->tagged('notification.channel-senders'),
            $app->make(\App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface::class),
            $app->make(\Illuminate\Database\DatabaseManager::class),
        ));
    }
}
