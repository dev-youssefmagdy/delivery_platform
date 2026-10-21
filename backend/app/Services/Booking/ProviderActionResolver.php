<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\ProviderBookingAction;
use App\Enums\Common\ActorType;
use App\Models\Booking;

/**
 * `allowed_actions` of the assigned provider's view of a booking. Mirrors the guards of the transition service: start is
 * offered only when the PIN is verified (or not required) and not locked.
 */
final class ProviderActionResolver
{
    public function __construct(private readonly RatingService $ratings) {}

    /**
     * @return list<ProviderBookingAction>
     */
    public function forProvider(Booking $booking): array
    {
        $actions = [];
        $pinRequired = (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false);

        switch ($booking->status) {
            case BookingStatus::ProviderSelected:
                $actions = [ProviderBookingAction::Acknowledge, ProviderBookingAction::Cancel];

                break;
            case BookingStatus::ProviderHeadingToPickup:
                $actions = [ProviderBookingAction::Arrived, ProviderBookingAction::Cancel];

                break;
            case BookingStatus::ProviderArrived:
                if ($pinRequired && $booking->pin_verified_at === null && $booking->pin_locked_at === null) {
                    $actions[] = ProviderBookingAction::VerifyStartPin;
                }

                if (! $pinRequired || $booking->pin_verified_at !== null) {
                    $actions[] = ProviderBookingAction::Start;
                }

                $actions[] = ProviderBookingAction::Cancel;

                break;
            case BookingStatus::InProgress:
                $actions = [ProviderBookingAction::Complete];

                break;
            default:
                break;
        }

        if ($this->ratings->canRate($booking, ActorType::Provider)) {
            $actions[] = ProviderBookingAction::RateCustomer;
        }

        if (BookingReportService::canReport($booking)) {
            $actions[] = ProviderBookingAction::Report;
        }

        return $actions;
    }
}
