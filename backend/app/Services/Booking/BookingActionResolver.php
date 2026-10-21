<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingAction;
use App\Enums\Booking\BookingStatus;
use App\Models\Booking;

/**
 * Computes `allowed_actions` for the customer's view of a booking. Apps render buttons from this list and never infer
 * them from the status; the endpoints re-check on every call. Actions of later milestones (cancel, adjust, rate) join
 * the list when their endpoints exist.
 */
final class BookingActionResolver
{
    /**
     * @return list<BookingAction>
     */
    public function forCustomer(Booking $booking): array
    {
        $actions = [];

        if ($booking->status->isSearching()) {
            $actions[] = BookingAction::UpdateRequest;
        }

        if ($booking->status === BookingStatus::OffersReceived) {
            $actions[] = BookingAction::SelectOffer;
        }

        return $actions;
    }
}
