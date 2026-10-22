<?php

namespace App\Http\Resources\Api\V1\Load;

use App\Enums\Common\ActorType;
use App\Models\Booking;
use App\Models\BookingAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * An attachment as its owner sees it. The file is reachable only through a short-lived signed URL that still needs the party's token;
 * the storage path, disk, hash and scan details are never returned. An unattached upload has no URL (nobody else can read it).
 *
 * @property BookingAttachment $resource
 */
final class BookingAttachmentResource extends JsonResource
{
    public function __construct(BookingAttachment $attachment, private readonly ?Booking $booking = null, private readonly ActorType $viewer = ActorType::Customer)
    {
        parent::__construct($attachment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attachment = $this->resource;

        return [
            'id' => $attachment->uuid,
            'kind' => $attachment->kind,
            'field' => $attachment->field,
            'mime' => $attachment->mime,
            'size_bytes' => $attachment->size_bytes,
            'attached' => $attachment->booking_id !== null,
            'created_at' => $attachment->created_at->toIso8601ZuluString(),
            'url' => $this->booking === null || $attachment->storage_path === null ? null : URL::temporarySignedRoute(
                $this->viewer === ActorType::Provider ? 'api.v1.provider.bookings.attachments.file' : 'api.v1.bookings.attachments.file',
                now()->addMinutes((int) config('offer_scope.attachments.url_ttl_minutes', 10)),
                ['booking' => $this->booking->uuid, 'attachment' => $attachment->uuid],
            ),
        ];
    }
}
