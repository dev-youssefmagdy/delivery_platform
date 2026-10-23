<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Settings\SettingKey;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\ShareLinkRepositoryInterface;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * "Share trip" (SRS §6.6, §13.1): a time-limited public link a customer sends to trusted people. The random token is
 * returned once and only its SHA-256 is stored. A booking has at most one live link (creating a new one revokes the old),
 * it lasts `share_link_ttl_minutes`, and it stops working when the trip ends (the transition service revokes it).
 */
final class ShareLinkService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ShareLinkRepositoryInterface $links,
        private readonly SettingsService $settings,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array{token: string, url: string, expires_at: CarbonImmutable}
     *
     * @throws ConflictException `share_not_available` unless a provider is selected and the trip is not finished
     */
    public function create(User $customer, string $bookingUuid): array
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->issue($customer, $booking);
    }

    /**
     * Issues the live link of a booking for either side (the customer, or the provider through the safety share, P5.1).
     *
     * @return array{token: string, url: string, expires_at: CarbonImmutable}
     *
     * @throws ConflictException `share_not_available`
     */
    public function issue(User $creator, Booking $booking): array
    {
        $customer = $creator;

        return $this->db->transaction(function () use ($customer, $booking): array {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking || ! $locked->status->isAssigned()) {
                throw ConflictException::make('share_not_available', context: ['booking_status' => $locked?->status->value ?? $booking->status->value]);
            }

            $now = CarbonImmutable::now();
            $this->links->revokeForBooking($locked->id, $now);

            $token = Str::random(43);
            $expiresAt = $now->addMinutes($this->settings->int(SettingKey::ShareLinkTtlMinutes));
            $this->links->create($locked->id, hash('sha256', $token), $customer->id, $expiresAt);

            return ['token' => $token, 'url' => route('website.trip-share', ['token' => $token]), 'expires_at' => $expiresAt];
        });
    }

    /**
     * The customer stops sharing: every live link of the booking ends.
     *
     * @return int links revoked
     */
    public function revoke(User $customer, string $bookingUuid): int
    {
        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->links->revokeForBooking($booking->id, CarbonImmutable::now());
    }

    /**
     * Ends the live links of a booking on behalf of its provider.
     */
    public function revokeForBooking(Booking $booking): int
    {
        return $this->links->revokeForBooking($booking->id, CarbonImmutable::now());
    }

    public static function canShare(BookingStatus $status): bool
    {
        return $status->isAssigned();
    }
}
