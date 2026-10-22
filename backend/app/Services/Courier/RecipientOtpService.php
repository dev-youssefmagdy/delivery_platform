<?php

namespace App\Services\Courier;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Booking\RecipientOtpDelivery;
use App\Enums\Booking\SupportFlagReason;
use App\Enums\Catalog\ProofStage;
use App\Enums\Catalog\ProofType;
use App\Enums\Common\ActorType;
use App\Events\Realtime\TripSupportFlagged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\RateLimitedException;
use App\Integrations\Sms\Contracts\SmsGateway;
use App\Integrations\Sms\Exceptions\SmsDeliveryException;
use App\Models\Booking;
use App\Models\BookingRecipientOtp;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\RecipientOtpRepositoryInterface;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\BookingTransitionService;
use App\Services\Dispatch\EventSequencer;
use App\Services\Identity\OtpService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The code a parcel recipient gives the provider at hand-over (SRS §5.1-C, §7.5).
 *
 * - Generated when the parcel is collected, sent to the RECIPIENT's phone through the `SmsGateway` adapter (the code is
 *   never logged outside the local-only `otp_dev` channel the log driver writes to, and never returned by any API).
 * - Stored only as an HMAC bound to the booking; compared in constant time.
 * - Attempts are cumulative per booking (a resend does not reset them, so resending cannot be used to keep guessing); at
 *   the limit the code is locked, the booking is flagged for support and both parties are told. Only operations lift it.
 * - A correct code stores a `recipient_otp` proof (stage delivery) which the completion guard requires.
 * - A failed SMS is recorded as failed and the provider may ask for a resend; delivery is never reported as sent when it
 *   was not.
 */
final class RecipientOtpService
{
    public function __construct(
        private readonly RecipientOtpRepositoryInterface $codes,
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly BookingTransitionService $transitions,
        private readonly BookingContactService $contacts,
        private readonly BookingNotifier $notifier,
        private readonly OtpService $testCodes,
        private readonly EventSequencer $sequencer,
        private readonly Container $container,
        private readonly DatabaseManager $db,
    ) {}

    public static function requiredFor(Booking $booking): bool
    {
        return (bool) ($booking->policy_snapshot['safety']['recipient_otp_required'] ?? false);
    }

    /**
     * Creates (or replaces) the code of a booking whose parcel was just collected and sends it. Runs inside the transaction
     * of the start action; the SMS goes out after it commits so a rolled-back start never texts a code that does not exist.
     */
    public function issue(Booking $booking): void
    {
        if (! self::requiredFor($booking)) {
            return;
        }

        $phone = $this->contacts->recipientPhone($booking);

        if ($phone === null) {
            // The request has no recipient phone: nothing can be sent; the hand-over needs operations (the guard still holds).
            Log::warning('courier.recipient_otp_without_phone', ['booking' => $booking->uuid]);

            return;
        }

        $test = $this->testCodes->testCodeFor($phone);
        $code = $test ?? $this->generate();
        $now = CarbonImmutable::now();

        $this->codes->upsert($booking->id, [
            'code_hash' => $this->hash($booking, $code),
            'attempts' => 0,
            'max_attempts' => (int) config('courier.recipient_otp.max_attempts', 5),
            'resend_count' => 0,
            'delivery_status' => $test === null ? RecipientOtpDelivery::Pending : RecipientOtpDelivery::Skipped,
            'delivery_driver' => null,
            'sent_at' => null,
            'resend_available_at' => $now->addSeconds((int) config('courier.recipient_otp.resend_cooldown_seconds', 60)),
            'verified_at' => null,
            'locked_at' => null,
        ]);

        if ($test === null) {
            $this->db->afterCommit(fn () => $this->deliver($booking->id, $phone, $code));
        }
    }

    /**
     * The provider asks for the code to be sent again (a new code replaces the old one).
     *
     * @throws RateLimitedException `recipient_otp_resend_too_soon`
     * @throws BusinessRuleViolation `recipient_otp_resend_limit`
     * @throws ConflictException `recipient_otp_locked`, `recipient_otp_not_ready`
     */
    public function resend(ProviderProfile $provider, string $bookingUuid): BookingRecipientOtp
    {
        $issued = $this->db->transaction(function () use ($provider, $bookingUuid): array {
            $booking = $this->lockOwned($provider, $bookingUuid);
            $otp = $this->ready($booking);

            if ($otp->locked_at !== null) {
                throw ConflictException::make('recipient_otp_locked', context: ['locked' => true]);
            }

            if ($otp->verified_at !== null) {
                return [$otp, null, null];
            }

            $now = CarbonImmutable::now();

            if ($otp->resend_available_at !== null && $otp->resend_available_at->greaterThan($now)) {
                throw RateLimitedException::afterSeconds('recipient_otp_resend_too_soon', (int) ceil($now->diffInSeconds($otp->resend_available_at, absolute: true)));
            }

            if ($otp->resend_count >= (int) config('courier.recipient_otp.max_resends', 3)) {
                throw BusinessRuleViolation::make('recipient_otp_resend_limit');
            }

            $phone = $this->contacts->recipientPhone($booking) ?? throw BusinessRuleViolation::make('recipient_otp_no_phone');
            $test = $this->testCodes->testCodeFor($phone);
            $code = $test ?? $this->generate();

            $otp = $this->codes->update($otp, [
                'code_hash' => $this->hash($booking, $code),
                'resend_count' => $otp->resend_count + 1,
                'delivery_status' => $test === null ? RecipientOtpDelivery::Pending : RecipientOtpDelivery::Skipped,
                'resend_available_at' => $now->addSeconds((int) config('courier.recipient_otp.resend_cooldown_seconds', 60)),
            ]);

            if ($test === null) {
                $this->db->afterCommit(fn () => $this->deliver($booking->id, $phone, $code));
            }

            return [$otp, $phone, $code];
        });

        return $issued[0]->refresh();
    }

    /**
     * Checks the code the recipient gave. Wrong entries are counted under the booking lock (and committed even though the
     * call then throws); the last allowed one locks the code.
     *
     * @return array{booking: Booking, replayed: bool}
     *
     * @throws BusinessRuleViolation `recipient_otp_invalid` (meta `attempts_remaining`), `recipient_otp_not_required`
     * @throws ConflictException `recipient_otp_locked`, `recipient_otp_not_ready`
     */
    public function verify(ProviderProfile $provider, string $bookingUuid, string $code): array
    {
        $outcome = $this->db->transaction(function () use ($provider, $bookingUuid, $code): array|ConflictException|BusinessRuleViolation {
            $booking = $this->lockOwned($provider, $bookingUuid);

            if (! self::requiredFor($booking)) {
                return BusinessRuleViolation::make('recipient_otp_not_required');
            }

            $otp = $this->codes->lockForBooking($booking->id);

            if ($otp instanceof BookingRecipientOtp && $otp->verified_at !== null) {
                return ['booking' => $booking, 'replayed' => true];
            }

            if ($booking->status !== BookingStatus::InProgress || $booking->custody_status === CustodyStatus::Returning || ! $otp instanceof BookingRecipientOtp) {
                return ConflictException::make('recipient_otp_not_ready', context: ['booking_status' => $booking->status->value]);
            }

            if ($otp->locked_at !== null) {
                return ConflictException::make('recipient_otp_locked', context: ['locked' => true]);
            }

            $now = CarbonImmutable::now();
            $entered = $this->normalise($code);

            if (hash_equals($otp->code_hash, $this->hash($booking, $entered))) {
                $this->codes->update($otp, ['verified_at' => $now]);
                $this->proofs->record([
                    'booking_id' => $booking->id,
                    'proof_type' => ProofType::RecipientOtp,
                    'stage' => ProofStage::Delivery,
                    'actor_type' => ActorType::Provider->value,
                    'actor_id' => $provider->id,
                    'metadata' => ['attempts_before' => $otp->attempts],
                ]);

                return ['booking' => $booking->refresh(), 'replayed' => false];
            }

            $attempts = $otp->attempts + 1;
            $locks = $attempts >= $otp->max_attempts;
            $this->codes->update($otp, ['attempts' => $attempts] + ($locks ? ['locked_at' => $now] : []));

            if ($locks) {
                $this->bookings->forceUpdate($booking, [
                    'support_flagged_at' => $now,
                    'support_flag_reason' => SupportFlagReason::RecipientOtpAttemptsExceeded->value,
                    'lock_version' => $booking->lock_version + 1,
                ]);
                $this->transitions->annotate($booking, 'recipient_otp_locked', new TransitionContext(ActorType::System, metadata: ['attempts' => $attempts]));
                $this->announceLock($booking);
                Log::warning('courier.recipient_otp_locked', ['booking' => $booking->uuid, 'provider' => $provider->id, 'attempts' => $attempts]);

                return ConflictException::make('recipient_otp_locked', context: ['locked' => true]);
            }

            return BusinessRuleViolation::make('recipient_otp_invalid', 'code', ['attempts_remaining' => $otp->max_attempts - $attempts]);
        });

        if ($outcome instanceof ConflictException || $outcome instanceof BusinessRuleViolation) {
            if ($outcome instanceof ConflictException && ($outcome->context['locked'] ?? false) === true) {
                $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id);

                if ($booking instanceof Booking && $booking->support_flag_reason === SupportFlagReason::RecipientOtpAttemptsExceeded->value) {
                    $this->notifier->recipientOtpLocked($booking);
                }
            }

            throw $outcome;
        }

        return $outcome;
    }

    /**
     * Operations lift the lock: attempts back to zero, a new code is NOT issued (the provider may resend). Call inside the
     * admin override transaction with the booking locked.
     */
    public function lift(Booking $booking): int
    {
        $otp = $this->codes->lockForBooking($booking->id) ?? throw (new ModelNotFoundException)->setModel(BookingRecipientOtp::class);
        $attempts = $otp->attempts;
        $this->codes->update($otp, ['attempts' => 0, 'locked_at' => null]);

        return $attempts;
    }

    /**
     * Safe facts about the code for the apps and the admin screen; never the code.
     *
     * @return array<string, mixed>|null null when the category has no recipient code
     */
    public function state(Booking $booking): ?array
    {
        if (! self::requiredFor($booking)) {
            return null;
        }

        $otp = $this->codes->find($booking->id);

        return [
            'required' => true,
            'issued' => $otp instanceof BookingRecipientOtp,
            'verified' => $otp?->verified_at !== null,
            'locked' => $otp?->locked_at !== null,
            'delivery_status' => $otp?->delivery_status->value,
            'attempts_remaining' => $otp === null || $otp->verified_at !== null ? null : max(0, $otp->max_attempts - $otp->attempts),
            'attempts_used' => $otp?->attempts,
            'max_attempts' => $otp?->max_attempts,
            'resend_available_at' => $otp?->resend_available_at?->toIso8601ZuluString(),
            'resends_left' => $otp === null ? null : max(0, (int) config('courier.recipient_otp.max_resends', 3) - $otp->resend_count),
        ];
    }

    private function lockOwned(ProviderProfile $provider, string $bookingUuid): Booking
    {
        $booking = $this->bookings->findByUuidForUpdate($bookingUuid);

        if (! $booking instanceof Booking || $booking->provider_id !== $provider->id) {
            throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        return $booking;
    }

    private function ready(Booking $booking): BookingRecipientOtp
    {
        $otp = $this->codes->lockForBooking($booking->id);

        if (! self::requiredFor($booking)) {
            throw BusinessRuleViolation::make('recipient_otp_not_required');
        }

        if (! $otp instanceof BookingRecipientOtp || $booking->status !== BookingStatus::InProgress || $booking->custody_status === CustodyStatus::Returning) {
            throw ConflictException::make('recipient_otp_not_ready', context: ['booking_status' => $booking->status->value]);
        }

        return $otp;
    }

    /**
     * Sends the code. Only the delivery outcome is stored; the exception messages never carry the code.
     */
    private function deliver(int $bookingId, string $phone, string $code): void
    {
        $otp = $this->codes->find($bookingId);

        if (! $otp instanceof BookingRecipientOtp) {
            return;
        }

        try {
            $gateway = $this->container->make(SmsGateway::class);
            $gateway->send($phone, (string) __('api.courier.recipient_otp_sms', ['code' => $code]));
            $this->codes->update($otp, ['delivery_status' => RecipientOtpDelivery::Sent, 'delivery_driver' => $gateway->name(), 'sent_at' => now()]);
        } catch (SmsDeliveryException $e) {
            Log::warning('courier.recipient_otp_sms_failed', ['booking_id' => $bookingId, 'reason' => $e->getMessage()]);
            $this->codes->update($otp, ['delivery_status' => RecipientOtpDelivery::Failed]);
        } catch (RuntimeException $e) {
            Log::error('courier.recipient_otp_gateway_unavailable', ['booking_id' => $bookingId, 'reason' => class_basename($e)]);
            $this->codes->update($otp, ['delivery_status' => RecipientOtpDelivery::Failed]);
        }
    }

    private function announceLock(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'provider']);
        $sequence = $this->sequencer->next($booking->uuid);
        $payload = ['reason' => SupportFlagReason::RecipientOtpAttemptsExceeded->value];

        TripSupportFlagged::toCustomer($booking->customer->uuid, $booking->uuid, $sequence, $payload);

        if ($booking->provider !== null) {
            TripSupportFlagged::toProvider($booking->provider->uuid, $booking->uuid, $sequence, $payload);
        }
    }

    private function generate(): string
    {
        $length = max(4, (int) config('courier.recipient_otp.length', 6));
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }

    private function normalise(string $code): string
    {
        return (string) preg_replace('/\s+/', '', strtr($code, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
    }

    private function hash(Booking $booking, string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key').'|recipient-otp|'.$booking->uuid);
    }
}
