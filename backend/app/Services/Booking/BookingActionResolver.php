<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingAction;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Models\Booking;

/**
 * Computes `allowed_actions` for the customer's view of a booking. Apps render buttons from this list and never infer
 * them from the status; the endpoints re-check on every call. Actions of later milestones (adjust, tip, repeat) join the
 * list when their endpoints exist.
 */
final class BookingActionResolver
{
    public function __construct(private readonly RatingService $ratings) {}

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

        if (in_array($booking->status, [
            BookingStatus::Draft, BookingStatus::SearchingOffers, BookingStatus::OffersReceived,
            BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived,
        ], true)) {
            $actions[] = BookingAction::Cancel;
        }

        if (ShareLinkService::canShare($booking->status)) {
            $actions[] = BookingAction::Share;
        }

        if ($this->ratings->canRate($booking, ActorType::Customer)) {
            $actions[] = BookingAction::Rate;
        }

        if (BookingReportService::canReport($booking)) {
            $actions[] = BookingAction::Report;
        }

        return $actions;
    }
}
