<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\ProviderBookingAction;
use App\Enums\Common\ActorType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Identity\ClientApp;
use App\Enums\Support\TicketSource;
use App\Models\Booking;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Support\Chat\AppealEligibility;
use App\Support\Chat\ChatWindow;

/**
 * `allowed_actions` of the assigned provider's view of a booking. Mirrors the guards of the transition service: start is
 * offered only when the PIN is verified (or not required) and not locked.
 */
final class ProviderActionResolver
{
    public function __construct(
        private readonly RatingService $ratings,
        private readonly SupportTicketRepositoryInterface $tickets,
    ) {}

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

        if (in_array($booking->status, [BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived, BookingStatus::InProgress], true)) {
            $actions[] = ProviderBookingAction::RequestAdjustment;
        }

        if (in_array($booking->status, [BookingStatus::Completed, BookingStatus::CompletedPendingPayment], true)) {
            $actions[] = ProviderBookingAction::ViewReceipt;
        }

        // A completed cash booking waits for the provider to confirm the cash was collected (P3.2).
        if ($booking->status === BookingStatus::Completed && $booking->payment_method === PaymentMethodType::Cash && $booking->payment_status === PaymentStatus::CashDue) {
            $actions[] = ProviderBookingAction::ConfirmCashCollected;
        }

        if ($this->ratings->canRate($booking, ActorType::Provider)) {
            $actions[] = ProviderBookingAction::RateCustomer;
        }

        if (BookingReportService::canReport($booking)) {
            $actions[] = ProviderBookingAction::Report;
        }

        if (ChatWindow::hasConversation($booking)) {
            $actions[] = ProviderBookingAction::Chat;
        }

        $providerUserId = $booking->provider?->user_id;

        if ($providerUserId !== null && AppealEligibility::allowed($booking, ClientApp::Provider) && $this->tickets->findForBookingAndSource($booking->id, $providerUserId, TicketSource::Appeal->value) === null) {
            $actions[] = ProviderBookingAction::Appeal;
        }

        return $actions;
    }
}
