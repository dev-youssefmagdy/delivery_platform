<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingAction;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Settings\SettingKey;
use App\Integrations\Payments\PaymentGatewayManager;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Services\Courier\DeliveryProofService;
use App\Enums\Identity\ClientApp;
use App\Enums\Support\TicketSource;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Chat\AppealEligibility;
use App\Support\Chat\ChatWindow;

/**
 * Computes `allowed_actions` for the customer's view of a booking. Apps render buttons from this list and never infer
 * them from the status; the endpoints re-check on every call. Actions of later milestones (adjust, tip, repeat) join the
 * list when their endpoints exist.
 */
final class BookingActionResolver
{
    public function __construct(
        private readonly RatingService $ratings,
        private readonly PaymentRepositoryInterface $payments,
        private readonly SettingsService $settings,
        private readonly PaymentGatewayManager $gateways,
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly DeliveryAttemptRepositoryInterface $attempts,
    ) {}

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

        if (in_array($booking->status, [BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived, BookingStatus::InProgress], true)) {
            $actions[] = BookingAction::RequestAdjustment;
        }

        if (in_array($booking->status, [BookingStatus::Completed, BookingStatus::CompletedPendingPayment], true)) {
            $actions[] = BookingAction::ViewReceipt;
        }

        if (ShareLinkService::canShare($booking->status)) {
            $actions[] = BookingAction::Share;
        }

        $online = in_array($booking->payment_method, [PaymentMethodType::Card, PaymentMethodType::MobileWallet], true) && $this->gateways->isConfigured();

        if ($online && $booking->status === BookingStatus::CompletedPendingPayment) {
            $actions[] = BookingAction::Pay;
        }

        // A tip is offered after the trip, never preselected, and only once per booking.
        if ($this->gateways->isConfigured() && $booking->status === BookingStatus::Completed && $booking->provider_id !== null
            && (bool) ($booking->policy_snapshot['payments']['tipping_enabled'] ?? false) && $this->settings->bool(SettingKey::TipsEnabled)
            && $this->payments->tipTotalMinor($booking->id) === 0) {
            $actions[] = BookingAction::Tip;
        }

        if ($this->ratings->canRate($booking, ActorType::Customer)) {
            $actions[] = BookingAction::Rate;
        }

        if (BookingReportService::canReport($booking)) {
            $actions[] = BookingAction::Report;
        }

        // Chat with the selected provider (reading stays possible after the window closes; the chat endpoint says whether sending is open).
        if (ChatWindow::hasConversation($booking)) {
            $actions[] = BookingAction::Chat;
        }

        // Parcels (P4.1): the customer can look at the proofs once the provider is on the job, and decides what happens after a
        // failed delivery attempt.
        if ($booking->provider_id !== null && DeliveryProofService::supported($booking)) {
            $actions[] = BookingAction::ViewProofs;
        }

        if ($booking->status === BookingStatus::DeliveryAttemptFailed && $this->attempts->latestFor($booking->id)?->isPending() === true) {
            $actions[] = BookingAction::ResolveDeliveryAttempt;
        }

        if (AppealEligibility::allowed($booking, ClientApp::Customer) && $this->tickets->findForBookingAndSource($booking->id, $booking->customer_id, TicketSource::Appeal->value) === null) {
            $actions[] = BookingAction::Appeal;
        }

        return $actions;
    }
}
