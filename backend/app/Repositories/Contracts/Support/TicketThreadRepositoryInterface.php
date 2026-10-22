<?php

namespace App\Repositories\Contracts\Support;

use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use Illuminate\Support\Collection;

/**
 * Messages, attachments and events of a ticket.
 */
interface TicketThreadRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function createMessage(array $attributes): TicketMessage;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createAttachment(array $attributes): TicketAttachment;

    /**
     * @param array<string, mixed>|null $meta
     */
    public function recordEvent(SupportTicket $ticket, string $type, string $actorType, ?int $actorId, ?array $meta = null): TicketEvent;

    /**
     * The thread, oldest first. `$includeInternal` is true only for staff screens; the user API always passes false.
     *
     * @return Collection<int, TicketMessage>
     */
    public function messages(SupportTicket $ticket, bool $includeInternal): Collection;

    public function findAttachment(SupportTicket $ticket, string $attachmentUuid, bool $includeInternal): ?TicketAttachment;

    /**
     * @return Collection<int, TicketEvent>
     */
    public function events(SupportTicket $ticket): Collection;
}
