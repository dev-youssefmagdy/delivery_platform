<?php

namespace App\Notifications\Admin;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password reset / invitation email: a link to the admin reset-password form (token expires per the `admins`
 * password broker, 60 minutes). The invitation variant is sent when an admin account is created.
 */
class AdminPasswordNotification extends Notification
{
    public function __construct(public readonly string $token, public readonly bool $invited = false) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\Admin $notifiable */
        $url = route('admin.password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
        $key = $this->invited ? 'invitation' : 'reset';

        return (new MailMessage)
            ->subject(__("admin.auth.mail.{$key}.subject"))
            ->greeting(__('admin.auth.mail.greeting', ['name' => $notifiable->name]))
            ->line(__("admin.auth.mail.{$key}.line"))
            ->action(__("admin.auth.mail.{$key}.action"), $url)
            ->line(__('admin.auth.mail.expires', ['minutes' => (int) config('auth.passwords.admins.expire', 60)]))
            ->line(__('admin.auth.mail.ignore'));
    }
}
