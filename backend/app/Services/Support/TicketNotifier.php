<?php

namespace App\Services\Support;

use App\Enums\Notification\NotificationEvent;
use App\Enums\Support\TicketSource;
use App\Models\SupportTicket;
use App\Services\Notification\PartyNotifier;
use Throwable;

/**
 * Tells the person behind a ticket what happened to it, through the notification platform (P3.6). Safety tickets use the
 * safety case event (essential, also by SMS), money and conduct disputes the dispute event, everything else the plain reply.
 * Failures are reported and never block the support action.
 */
final class TicketNotifier
{
    public function __construct(private readonly PartyNotifier $notifier) {}

    /**
     * A public staff reply.
     */
    public function replied(SupportTicket $ticket, string $messageUuid): void
    {
        $this->send($ticket, $this->eventFor($ticket), $messageUuid);
    }

    /**
     * The ticket's status changed in a way the person should know (resolved, reopened by staff, escalated is internal and not sent).
     */
    public function statusChanged(SupportTicket $ticket, string $status, string $discriminator): void
    {
        $this->send($ticket, $ticket->category->isSafety() ? NotificationEvent::SafetyCaseUpdate : ($ticket->category->isDispute() ? NotificationEvent::SupportDisputeUpdate : NotificationEvent::SupportReply), $status.'-'.$discriminator);
    }

    private function eventFor(SupportTicket $ticket): NotificationEvent
    {
        return $ticket->category->isSafety() ? NotificationEvent::SafetyCaseUpdate : NotificationEvent::SupportReply;
    }

    private function send(SupportTicket $ticket, NotificationEvent $event, string $discriminator): void
    {
        // A website ticket belongs to the placeholder account: there is nobody to notify (staff answer by email).
        if ($ticket->source === TicketSource::Website) {
            return;
        }

        try {
            $ticket->loadMissing('user');
            $this->notifier->user($ticket->user, $ticket->app, $event, $discriminator, [], $ticket->uuid);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
