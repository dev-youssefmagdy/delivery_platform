<?php

namespace App\Services\Booking;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\SupportFlagReason;
use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Enums\Settings\SettingKey;
use App\Events\Realtime\TripSupportFlagged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Dispatch\EventSequencer;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Verifies the trip start PIN the customer shows at pickup (SRS §6.6, §7.5). The PIN is stored encrypted so the customer
 * can read it, and compared with `hash_equals` (constant time). Wrong entries are counted under the booking lock; at the
 * limit (setting `ride_pin_max_attempts`) the start is locked, the booking is flagged for support and both parties are
 * told. A lock is only lifted by operations (P2.8). A correct entry stores a `pickup_pin` proof.
 */
final class TripPinService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly BookingTransitionService $transitions,
        private readonly SettingsService $settings,
        private readonly EventSequencer $sequencer,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array{booking: Booking, replayed: bool}
     *
     * @throws BusinessRuleViolation `trip_pin_not_required`, `trip_pin_invalid` (meta `attempts_remaining`)
     * @throws ConflictException `trip_pin_not_ready`, `trip_pin_locked`
     */
    public function verify(ProviderProfile $provider, string $bookingUuid, string $pin): array
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        $outcome = $this->db->transaction(function () use ($provider, $booking, $pin): array|ConflictException|BusinessRuleViolation {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking || $locked->provider_id !== $provider->id) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if (! (bool) ($locked->policy_snapshot['safety']['pin_required'] ?? false) || $locked->pin_encrypted === null) {
                return BusinessRuleViolation::make('trip_pin_not_required');
            }

            if ($locked->pin_verified_at !== null) {
                return ['booking' => $locked, 'replayed' => true];
            }

            if ($locked->status !== BookingStatus::ProviderArrived) {
                return ConflictException::make('trip_pin_not_ready', context: ['booking_status' => $locked->status->value]);
            }

            if ($locked->pin_locked_at !== null) {
                return ConflictException::make('trip_pin_locked', context: ['locked' => true]);
            }

            $now = CarbonImmutable::now();

            if (hash_equals(Crypt::decryptString($locked->pin_encrypted), $pin)) {
                $this->bookings->forceUpdate($locked, ['pin_verified_at' => $now]);
                $this->proofs->record([
                    'booking_id' => $locked->id,
                    'proof_type' => ProofType::PickupPin,
                    'actor_type' => ActorType::Provider->value,
                    'actor_id' => $provider->id,
                    'metadata' => ['attempts_before' => $locked->pin_attempts],
                ]);

                return ['booking' => $locked->refresh(), 'replayed' => false];
            }

            $attempts = $locked->pin_attempts + 1;
            $max = $this->settings->int(SettingKey::RidePinMaxAttempts);
            $update = ['pin_attempts' => $attempts];

            if ($attempts >= $max) {
                $update += ['pin_locked_at' => $now, 'support_flagged_at' => $now, 'support_flag_reason' => SupportFlagReason::PinAttemptsExceeded->value];
            }

            $this->bookings->forceUpdate($locked, $update);

            if ($attempts >= $max) {
                $this->transitions->annotate($locked, 'pin_locked', new TransitionContext(ActorType::System, metadata: ['attempts' => $attempts]));
                $this->announceLock($locked);
                Log::warning('booking.pin_locked', ['booking' => $locked->uuid, 'provider' => $provider->id, 'attempts' => $attempts]);

                return ConflictException::make('trip_pin_locked', context: ['locked' => true]);
            }

            return BusinessRuleViolation::make('trip_pin_invalid', 'pin', ['attempts_remaining' => $max - $attempts]);
        });

        if ($outcome instanceof ConflictException || $outcome instanceof BusinessRuleViolation) {
            throw $outcome;
        }

        return $outcome;
    }

    private function announceLock(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'provider']);
        $sequence = $this->sequencer->next($booking->uuid);
        $payload = ['reason' => SupportFlagReason::PinAttemptsExceeded->value];

        TripSupportFlagged::toCustomer($booking->customer->uuid, $booking->uuid, $sequence, $payload);

        if ($booking->provider !== null) {
            TripSupportFlagged::toProvider($booking->provider->uuid, $booking->uuid, $sequence, $payload);
        }
    }
}
