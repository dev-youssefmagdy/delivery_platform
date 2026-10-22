<?php

namespace App\Services\Scope;

use App\DTOs\Booking\ValidatedBookingRequest;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingItemRepositoryInterface;

/**
 * Writes the structured load of a moving / freight request next to the booking, inside the booking's transaction: the item rows
 * (checklist, free text, cargo line) and the links to the uploaded attachments. Called at creation and again, in full, when the
 * customer changes the request, so rows never drift from `service_details`.
 */
final class BookingLoadService
{
    public function __construct(
        private readonly BookingItemRepositoryInterface $items,
        private readonly BookingAttachmentService $attachments,
    ) {}

    public function store(Booking $booking, int $customerId, ValidatedBookingRequest $validated): void
    {
        $this->items->replace($booking, $validated->items);
        $this->attachments->sync($booking, $customerId, $validated->attachmentRefs);
    }
}
