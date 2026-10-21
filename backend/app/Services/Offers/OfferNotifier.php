<?php

namespace App\Services\Offers;

use App\Enums\Booking\OfferStatus;
use App\Events\Realtime\BookingOfferExpired;
use App\Events\Realtime\BookingOfferReceived;
use App\Events\Realtime\BookingOfferUpdated;
use App\Events\Realtime\BookingProviderSelected;
use App\Http\Resources\Api\V1\Offer\CustomerOfferResource;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Services\Dispatch\EventSequencer;

/**
 * Publishes the negotiation events (SRS §10.4). Everything is dispatched after commit by the event base class, so a
 * rolled-back selection announces nothing. Offer cards go to the customer's channel, decisions to the provider's own
 * channel; nothing about an offer goes to the booking channel that every dispatched provider can join (Plan I10).
 */
final class OfferNotifier
{
    public function __construct(
        private readonly EventSequencer $sequencer,
        private readonly OfferRepositoryInterface $offers,
    ) {}

    public function received(Booking $booking, BookingOffer $offer): void
    {
        $booking->loadMissing('customer');
        BookingOfferReceived::toCustomer($booking->customer->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), $this->cardPayload($booking, $offer));
    }

    public function revised(Booking $booking, BookingOffer $offer): void
    {
        $booking->loadMissing('customer');
        BookingOfferUpdated::toCustomer($booking->customer->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), $this->cardPayload($booking, $offer));
    }

    /**
     * An offer ended without being chosen: the customer drops the card, the provider learns why.
     */
    public function closed(Booking $booking, BookingOffer $offer, OfferStatus $status): void
    {
        $booking->loadMissing('customer');
        $offer->loadMissing('providerProfile');
        $payload = ['offer_id' => $offer->uuid, 'booking_id' => $booking->uuid, 'status' => $status->value, 'state' => $status->providerView()];

        BookingOfferExpired::toCustomer($booking->customer->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), $payload);
        BookingOfferExpired::toProvider($offer->providerProfile->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), $payload);
    }

    /**
     * The customer declined the offer: only its provider is told.
     */
    public function declined(Booking $booking, BookingOffer $offer): void
    {
        $offer->loadMissing('providerProfile');
        BookingOfferUpdated::toProvider($offer->providerProfile->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), [
            'offer_id' => $offer->uuid,
            'booking_id' => $booking->uuid,
            'status' => OfferStatus::RejectedByCustomer->value,
            'state' => OfferStatus::RejectedByCustomer->providerView(),
        ]);
    }

    public function selected(Booking $booking, BookingOffer $offer): void
    {
        $booking->loadMissing('customer');
        $offer->loadMissing('providerProfile');

        BookingProviderSelected::toCustomer($booking->customer->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), $this->cardPayload($booking, $offer) + ['status' => $booking->status->value]);
        BookingProviderSelected::toProvider($offer->providerProfile->uuid, $booking->uuid, $this->sequencer->next($booking->uuid), [
            'offer_id' => $offer->uuid,
            'booking_id' => $booking->uuid,
            'status' => $booking->status->value,
            'agreed_fare' => ['amount_minor' => $booking->agreed_fare_minor, 'currency' => $booking->currency],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(Booking $booking, BookingOffer $offer): array
    {
        $card = (new CustomerOfferResource($this->offers->loadCard($offer)))->resolve();

        return ['offer' => $card, 'booking_id' => $booking->uuid];
    }
}
