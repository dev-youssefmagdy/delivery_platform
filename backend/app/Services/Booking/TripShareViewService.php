<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingStatus;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Contracts\Booking\ShareLinkRepositoryInterface;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * What the public share page may show (SRS §13.1: "reveal only necessary data"): status, the provider's first name, the
 * vehicle, the live position while it is fresh and the planned duration as an estimate, plus when the link ends. Never
 * a name of the customer, an address, a phone number, a fare or an exact route. Returns null for an unknown, expired,
 * revoked or finished share, and the page answers 404 / 410 without saying which.
 */
final class TripShareViewService
{
    public function __construct(
        private readonly ShareLinkRepositoryInterface $links,
        private readonly OfferRepositoryInterface $offers,
        private readonly ProviderLastLocationRepositoryInterface $locations,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function view(string $token, ?CarbonImmutable $now = null): ?array
    {
        $now ??= CarbonImmutable::now();
        $link = $this->links->findByHash(hash('sha256', $token));

        if ($link === null || ! $link->isActiveAt($now) || ! $link->booking instanceof Booking) {
            return null;
        }

        $booking = $link->booking;

        if (! $booking->status->isAssigned()) {
            return null;
        }

        $offer = $booking->selected_offer_id === null ? null : $this->offers->find($booking->selected_offer_id);
        $offer = $offer instanceof BookingOffer ? $this->offers->loadCard($offer) : null;
        $user = $offer?->providerProfile->user;
        $vehicle = $offer?->vehicle;

        return [
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'in_progress' => $booking->status === BookingStatus::InProgress,
            'provider_first_name' => $user === null ? null : (preg_split('/\s+/u', trim((string) $user->name))[0] ?? null),
            'vehicle' => $vehicle === null ? null : [
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'plate' => $vehicle->plate_display,
            ],
            'position' => $this->position($booking, $now),
            'estimated_trip_minutes' => isset($booking->route_snapshot['duration_s']) ? (int) ceil(((int) $booking->route_snapshot['duration_s']) / 60) : null,
            'expires_at' => $link->expires_at->toImmutable(),
        ];
    }

    /**
     * @return array{lat: float, lng: float, recorded_at: CarbonImmutable}|null
     */
    private function position(Booking $booking, CarbonImmutable $now): ?array
    {
        if ($booking->provider === null) {
            return null;
        }

        $last = $this->locations->findFor($booking->provider);
        $maxAge = (int) config('booking.trip.share_location_max_age_seconds', 180);

        if ($last === null || $last->recorded_at->lessThan($now->subSeconds($maxAge))) {
            return null;
        }

        return ['lat' => round($last->point->lat, 5), 'lng' => round($last->point->lng, 5), 'recorded_at' => CarbonImmutable::instance($last->recorded_at)];
    }
}
