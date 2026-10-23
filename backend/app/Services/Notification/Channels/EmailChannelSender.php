<?php

namespace App\Services\Notification\Channels;

use App\Enums\Notification\NotificationChannel;
use App\Mail\NotificationMail;
use App\Models\Admin;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notification\DeliveryOutcome;
use Illuminate\Contracts\Mail\Mailer;
use App\Enums\System\HealthArea;
use App\Services\System\IntegrationStatusStore;
use Throwable;

/**
 * Email to the person's address through Laravel's mailer (SMTP / SES / Postmark by configuration). A person without an
 * address is skipped, not failed.
 */
final class EmailChannelSender implements ChannelSender
{
    public function __construct(private readonly Mailer $mailer) {}

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Email;
    }

    public function send(NotificationDelivery $delivery, User|Admin $recipient): DeliveryOutcome
    {
        $address = $recipient->email;

        if ($address === null || $address === '') {
            return DeliveryOutcome::skipped('no_email');
        }

        if (config('integrations.email.driver') === null) {
            return DeliveryOutcome::skipped('email_not_configured');
        }

        try {
            $this->mailer->to($address)->send(new NotificationMail((string) $delivery->title, $delivery->body, $delivery->notification->locale));
        } catch (Throwable $e) {
            IntegrationStatusStore::failed(HealthArea::Email, $e);

            // The transport message can contain the address or credentials: only the code is stored.
            return DeliveryOutcome::retry('email_failed', 'The mail server did not accept the message.', (string) config('mail.default'));
        }

        IntegrationStatusStore::succeeded(HealthArea::Email);

        return DeliveryOutcome::sent((string) config('mail.default'));
    }
}
