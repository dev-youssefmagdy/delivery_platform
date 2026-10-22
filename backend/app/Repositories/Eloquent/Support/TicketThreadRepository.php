<?php

namespace App\Repositories\Eloquent\Support;

use App\Enums\Common\ActorType;
use App\Enums\Support\TicketEventType;
use App\Enums\Support\TicketVisibility;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use App\Repositories\Contracts\Support\TicketThreadRepositoryInterface;
use Illuminate\Support\Collection;

final class TicketThreadRepository implements TicketThreadRepositoryInterface
{
    public function createMessage(array $attributes): TicketMessage
    {
        return TicketMessage::query()->create($attributes);
    }

    public function createAttachment(array $attributes): TicketAttachment
    {
        return TicketAttachment::query()->create($attributes);
    }

    public function recordEvent(SupportTicket $ticket, string $type, string $actorType, ?int $actorId, ?array $meta = null): TicketEvent
    {
        return TicketEvent::query()->create([
            'ticket_id' => $ticket->id,
            'type' => TicketEventType::from($type),
            'actor_type' => ActorType::from($actorType),
            'actor_id' => $actorId,
            'meta' => $meta,
        ]);
    }

    public function messages(SupportTicket $ticket, bool $includeInternal): Collection
    {
        return TicketMessage::query()
            ->with(['attachments', 'authorAdmin:id,name'])
            ->where('ticket_id', $ticket->id)
            ->when(! $includeInternal, static fn ($query) => $query->where('visibility', TicketVisibility::Public->value))
            ->orderBy('id')
            ->get()
            ->toBase();
    }

    public function findAttachment(SupportTicket $ticket, string $attachmentUuid, bool $includeInternal): ?TicketAttachment
    {
        $attachment = TicketAttachment::query()->with('message:id,visibility')->where('ticket_id', $ticket->id)->where('uuid', $attachmentUuid)->first();

        if ($attachment === null) {
            return null;
        }

        if (! $includeInternal && $attachment->message?->isInternal()) {
            return null;
        }

        return $attachment;
    }

    public function events(SupportTicket $ticket): Collection
    {
        return TicketEvent::query()->where('ticket_id', $ticket->id)->orderBy('id')->get()->toBase();
    }
}
