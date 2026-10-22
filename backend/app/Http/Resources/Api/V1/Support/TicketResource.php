<?php

namespace App\Http\Resources\Api\V1\Support;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * A ticket as its owner sees it. NEVER includes internal notes, internal events, the assignee, the group or the SLA: those
 * are staff-only. The thread is added only when the caller loaded the public messages (`$withThread`).
 *
 * @property SupportTicket $resource
 */
class TicketResource extends JsonResource
{
    /**
     * @param iterable<TicketMessage>|null $thread public messages only
     */
    public function __construct(SupportTicket $resource, private readonly ?iterable $thread = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ticket = $this->resource;
        $data = [
            'id' => $ticket->uuid,
            'number' => $ticket->number,
            'category' => $ticket->category->value,
            'category_label' => $ticket->category->label(),
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'subject' => $ticket->subject,
            'source' => $ticket->source->value,
            'booking_id' => $ticket->relationLoaded('booking') ? $ticket->booking?->uuid : null,
            'booking_code' => $ticket->relationLoaded('booking') ? $ticket->booking?->code : null,
            'can_reply' => $ticket->status->value !== 'closed' && ($ticket->status->value !== 'resolved' || ($ticket->resolved_at !== null && $ticket->resolved_at->addDays((int) config('support.tickets.reopen_days', 7))->isFuture())),
            'resolution' => $ticket->status->isFinished() ? $ticket->resolution_reason : null,
            'last_message_at' => $ticket->last_message_at?->toIso8601ZuluString(),
            'created_at' => $ticket->created_at->toIso8601ZuluString(),
        ];

        if ($this->thread !== null) {
            $data['messages'] = collect($this->thread)->map(fn (TicketMessage $message): array => [
                'id' => $message->uuid,
                'from' => $message->author_type->value === 'admin' ? 'support' : 'me',
                'body' => $message->body,
                'attachments' => $message->attachments->map(fn ($attachment): array => [
                    'id' => $attachment->uuid,
                    'name' => $attachment->original_name,
                    'mime' => $attachment->mime,
                    'size_bytes' => $attachment->size_bytes,
                    'url' => URL::temporarySignedRoute('api.v1.support.tickets.attachment', now()->addMinutes(5), ['ticket' => $ticket->uuid, 'attachment' => $attachment->uuid]),
                ])->values()->all(),
                'created_at' => $message->created_at->toIso8601ZuluString(),
            ])->values()->all();
        }

        return $data;
    }
}
