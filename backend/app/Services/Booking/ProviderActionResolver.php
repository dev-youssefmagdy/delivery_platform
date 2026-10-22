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
use App\Enums\Booking\CustodyStatus;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Services\Courier\CustodyService;
use App\Services\Courier\DeliveryProofService;
use App\Services\Courier\RecipientOtpService;
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
        private readonly DeliveryAttemptRepositoryInterface $attempts,
        private readonly RecipientOtpService $recipientOtps,
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

                if (DeliveryProofService::supported($booking)) {
                    $actions[] = ProviderBookingAction::UploadProof;
                }

                break;
            case BookingStatus::InProgress:
                $actions = [ProviderBookingAction::Complete];

                if (DeliveryProofService::supported($booking)) {
                    $actions[] = ProviderBookingAction::UploadProof;
                }

                $actions = [...$actions, ...$this->parcelActions($booking)];

                break;
            case BookingStatus::DeliveryAttemptFailed:
                if ($this->attempts->latestFor($booking->id)?->isPending() === true) {
                    $actions[] = ProviderBookingAction::ResolveDeliveryAttempt;
                }

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

    /**
     * Custody steps, the recipient code and the failed-attempt report of a parcel that is on its way (never on the way back to
     * the sender, which needs only the return photo and the completion).
     *
     * @return list<ProviderBookingAction>
     */
    private function parcelActions(Booking $booking): array
    {
        if (! CustodyService::applies($booking) || $booking->custody_status === null || $booking->custody_status === CustodyStatus::Returning) {
            return [];
        }

        $actions = [];

        if ($booking->custody_status === CustodyStatus::PickedUp) {
            $actions[] = ProviderBookingAction::MarkInTransit;
        }

        if (in_array($booking->custody_status, [CustodyStatus::PickedUp, CustodyStatus::InTransit], true)) {
            $actions[] = ProviderBookingAction::ArrivedDropoff;
        }

        $otp = $this->recipientOtps->state($booking);

        if ($otp !== null && $otp['issued'] === true && $otp['verified'] === false && $otp['locked'] === false) {
            $actions[] = ProviderBookingAction::VerifyRecipientOtp;
            $actions[] = ProviderBookingAction::ResendRecipientOtp;
        }

        if ($booking->custody_status === CustodyStatus::ArrivedDropoff) {
            $actions[] = ProviderBookingAction::ReportDeliveryAttempt;
        }

        return $actions;
    }
}
