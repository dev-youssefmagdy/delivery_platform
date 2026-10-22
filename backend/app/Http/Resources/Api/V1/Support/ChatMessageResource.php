<?php

namespace App\Http\Resources\Api\V1\Support;

use App\Enums\Common\ActorType;
use App\Models\BookingMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * A chat message as one side sees it. `mine` is decided from the reader's role; the image is a short-lived signed URL
 * (still requires the participant's token).
 *
 * @property BookingMessage $resource
 */
class ChatMessageResource extends JsonResource
{
    public function __construct(BookingMessage $resource, private readonly ActorType $role, private readonly string $bookingUuid)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $message = $this->resource;
        $route = $this->role === ActorType::Provider ? 'api.v1.provider.bookings.messages.attachment' : 'api.v1.bookings.messages.attachment';

        return [
            'id' => $message->uuid,
            'sender' => $message->sender_type->value,
            'mine' => $message->sender_type === $this->role,
            'body' => $message->body,
            'attachment' => $message->hasAttachment() ? [
                'mime' => $message->attachment_mime,
                'size_bytes' => $message->attachment_size,
                'url' => URL::temporarySignedRoute($route, now()->addMinutes(5), ['booking' => $this->bookingUuid, 'message' => $message->uuid]),
            ] : null,
            'purged' => $message->purged_at !== null,
            'read_at' => $message->read_at?->toIso8601ZuluString(),
            'created_at' => $message->created_at->toIso8601ZuluString(),
        ];
    }
}
