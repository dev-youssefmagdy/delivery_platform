<?php

namespace App\Services\Safety;

use App\Enums\Identity\ClientApp;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\RateLimitedException;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Services\Booking\ShareLinkService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * "Share trip with my trusted contacts" (SRS §13.1) for either app: issues the time-limited public link and texts it to the
 * contacts who consented to receive it; each contact gets an action record. The link shows only what the public share page shows.
 */
final class TripShareToContactsService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ShareLinkService $links,
        private readonly TrustedContactRepositoryInterface $contacts,
        private readonly TrustedContactNotifier $notifier,
    ) {}

    /**
     * @return array{url: string, expires_at: CarbonImmutable, total: int, sent: int, failed: int, skipped: int}
     *
     * @throws BusinessRuleViolation `trusted_contacts_none_for_trip`
     * @throws ConflictException `share_not_available`
     * @throws RateLimitedException `safety_trip_share_rate_limited`
     */
    public function share(User $user, ClientApp $app, string $bookingUuid): array
    {
        $booking = $this->booking($user, $app, $bookingUuid);

        if (! ShareLinkService::canShare($booking->status)) {
            throw ConflictException::make('share_not_available', context: ['booking_status' => $booking->status->value]);
        }

        if (! $this->contacts->forUser($user->id)->contains(static fn ($contact): bool => $contact->share_trip)) {
            throw BusinessRuleViolation::make('trusted_contacts_none_for_trip');
        }

        if ($this->contacts->tripSharesSince($user->id, $booking->id, CarbonImmutable::now()->subHour()) >= (int) config('safety.trip_share.per_hour', 6)) {
            throw RateLimitedException::afterSeconds('safety_trip_share_rate_limited', 600);
        }

        $link = $this->links->issue($user, $booking);

        return ['url' => $link['url'], 'expires_at' => $link['expires_at']] + $this->notifier->tripShare($user, $booking, $link['url'], $link['expires_at']);
    }

    public function stop(User $user, ClientApp $app, string $bookingUuid): int
    {
        return $this->links->revokeForBooking($this->booking($user, $app, $bookingUuid));
    }

    private function booking(User $user, ClientApp $app, string $uuid): Booking
    {
        if ($app === ClientApp::Customer) {
            return $this->bookings->findOfCustomer($uuid, $user->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        $user->loadMissing('providerProfile');

        return $this->bookings->findAssignedToProvider($uuid, ($user->providerProfile ?? throw (new ModelNotFoundException)->setModel(Booking::class))->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
    }
}
