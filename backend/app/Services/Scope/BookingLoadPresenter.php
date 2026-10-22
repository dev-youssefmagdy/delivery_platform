<?php

namespace App\Services\Scope;

use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingItem;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingItemRepositoryInterface;
use stdClass;

/**
 * The moving / freight blocks of a booking as BOTH apps read them: the load (items, weight, volume, pickup window, attachment
 * counts), the scope the customer requested and the accepted scope, each with the same translated summary lines, so the customer and
 * the selected provider always see an identical agreement (SRS §7.4). Null for categories without any of it.
 */
final class BookingLoadPresenter
{
    public function __construct(
        private readonly BookingItemRepositoryInterface $items,
        private readonly BookingAttachmentRepositoryInterface $attachments,
        private readonly OfferScopeService $scopes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function blocks(Booking $booking): array
    {
        return [
            'load' => $this->load($booking),
            'requested_scope' => ($booking->requested_scope ?? []) === [] ? new stdClass : $booking->requested_scope,
            'requested_scope_summary' => $this->scopes->summary($booking->requested_scope),
            'agreed_scope_summary' => $this->scopes->summary($booking->agreed_scope),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function load(Booking $booking): ?array
    {
        $items = $this->items->forBooking($booking->id);
        $attachments = $this->attachments->forBooking($booking->id);

        if ($items->isEmpty() && $attachments->isEmpty() && $booking->cargo_volume_m3 === null && $booking->pickup_window_start === null && $booking->cargo_weight_kg === null) {
            return null;
        }

        return [
            'weight_kg' => $booking->cargo_weight_kg === null ? null : (float) $booking->cargo_weight_kg,
            'volume_m3' => $booking->cargo_volume_m3 === null ? null : (float) $booking->cargo_volume_m3,
            'declared_value_minor' => $booking->declared_value_minor,
            'pickup_window' => $booking->pickup_window_start === null ? null : ['start' => $booking->pickup_window_start->toIso8601ZuluString(), 'end' => $booking->pickup_window_end?->toIso8601ZuluString()],
            'items' => $items->map(static fn (BookingItem $item): array => [
                'id' => $item->uuid,
                'kind' => $item->kind,
                'code' => $item->code,
                'label' => $item->label,
                'quantity' => $item->quantity,
                'dimensions_cm' => $item->length_cm === null ? null : ['length' => (float) $item->length_cm, 'width' => (float) $item->width_cm, 'height' => (float) $item->height_cm],
                'volume_m3' => $item->volume_m3 === null ? null : (float) $item->volume_m3,
                'weight_kg' => $item->weight_kg === null ? null : (float) $item->weight_kg,
                'note' => $item->note,
            ])->values()->all(),
            'attachments' => [
                'photos' => $attachments->where('kind', BookingAttachment::KIND_PHOTO)->count(),
                'videos' => $attachments->where('kind', BookingAttachment::KIND_VIDEO)->count(),
                'documents' => $attachments->where('kind', BookingAttachment::KIND_DOCUMENT)->count(),
            ],
        ];
    }
}
