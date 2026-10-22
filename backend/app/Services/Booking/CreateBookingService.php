<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingCreationResult;
use App\DTOs\Coupon\CouponPlan;
use App\DTOs\Booking\CreateBookingData;
use App\DTOs\Booking\TransitionContext;
use App\DTOs\Booking\ValidatedBookingRequest;
use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Settings\SettingKey;
use App\Exceptions\ConflictException;
use App\Exceptions\CouponRejectedException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStopRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Services\Coupon\CouponBookingService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Creates a booking request (SRS §6.4, Plan P2.3). Order matters: rules and the route estimate run first and outside
 * any lock (the maps call is slow); then one transaction holds the customer's row so two taps cannot both pass the
 * one-active-on-demand-booking rule or the same Idempotency-Key, inserts the booking with its snapshots and stops,
 * writes the creation event and moves it `draft -> searching_offers`. Dispatch and the expiry timer start after commit
 * from the `BookingEnteredSearch` listener. A replay of the same key returns the booking that already exists.
 */
final class CreateBookingService
{
    public function __construct(
        private readonly BookingRequestValidator $validator,
        private readonly BookingSnapshotBuilder $snapshots,
        private readonly BookingCodeGenerator $codes,
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingStopRepositoryInterface $stops,
        private readonly CustomerRepositoryInterface $customers,
        private readonly BookingTransitionService $transitions,
        private readonly SettingsService $settings,
        private readonly CouponBookingService $coupons,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ForbiddenActionException the account may not book (suspended, restricted ...)
     * @throws ConflictException `customer_has_active_booking`
     */
    public function create(User $customer, CreateBookingData $data): BookingCreationResult
    {
        $this->assertMayBook($customer);

        $replay = $this->replayOf($customer, $data);

        if ($replay instanceof Booking) {
            return new BookingCreationResult($replay, true);
        }

        $now = CarbonImmutable::now();
        $validated = $this->validator->validate($data, $now);
        // Coupon pre-check outside the transaction so a refused code is always logged (SRS §11.2 step 8); the reservation itself
        // is made inside it, under the coupon row lock, once the booking exists.
        $coupon = $this->coupons->prepare($customer, $data->couponCode, $validated);

        try {
            return $this->createLocked($customer, $data, $validated, $now, $coupon);
        } catch (CouponRejectedException $exception) {
            if ($coupon instanceof CouponPlan) {
                $this->coupons->recordFailure($coupon, $customer, $exception, $validated->category->id);

                // A suggested (automatic) promotion that ran out in the meantime never blocks the booking.
                if ($coupon->automatic) {
                    return $this->createLocked($customer, $data, $validated, $now, null);
                }
            }

            throw $exception;
        }
    }

    private function createLocked(User $customer, CreateBookingData $data, ValidatedBookingRequest $validated, CarbonImmutable $now, ?CouponPlan $coupon): BookingCreationResult
    {
        return $this->db->transaction(function () use ($customer, $data, $validated, $now, $coupon): BookingCreationResult {
            $locked = $this->customers->lockForUpdate($customer->id) ?? throw (new ModelNotFoundException)->setModel(User::class);
            $this->assertMayBook($locked);

            $replay = $this->replayOf($locked, $data);

            if ($replay instanceof Booking) {
                return new BookingCreationResult($replay, true);
            }

            if ($validated->bookingType === BookingType::OnDemand
                && $this->bookings->inFlightOnDemandCount($locked->id) >= $this->settings->int(SettingKey::MaxActiveOnDemandBookings)) {
                throw ConflictException::make('customer_has_active_booking');
            }

            $booking = $this->insert($locked, $data, $validated);
            $context = TransitionContext::customer($locked);
            $this->stops->replaceFor($booking, array_map(static fn ($stop): array => ['point' => $stop->point, 'address' => $stop->addressOrNull()], $validated->stops));
            $this->transitions->recordCreation($booking, $context);

            if ($coupon instanceof CouponPlan) {
                $booking = $this->coupons->reserve($coupon, $booking, $validated);
            }

            if ($this->searchOpensNow($validated, $now)) {
                $booking = $this->transitions->transition($booking, BookingStatus::SearchingOffers, $context);
            }

            return new BookingCreationResult($booking->load(['stops', 'statusEvents']));
        });
    }

    private function assertMayBook(User $customer): void
    {
        $code = $customer->status->blockCode(strict: true);

        if ($code !== null) {
            throw ForbiddenActionException::make($code);
        }
    }

    private function replayOf(User $customer, CreateBookingData $data): ?Booking
    {
        return $data->idempotencyKey === null ? null : $this->bookings->findByIdempotencyKey($customer->id, $data->idempotencyKey);
    }

    private function insert(User $customer, CreateBookingData $data, ValidatedBookingRequest $validated): Booking
    {
        $attempts = (int) config('booking.code.max_attempts', 8);

        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->bookings->insertDraft($this->snapshots->requestAttributes($validated) + [
                    'code' => $this->codes->generate(),
                    'customer_id' => $customer->id,
                    'payment_status' => PaymentStatus::Pending,
                    'idempotency_key' => $data->idempotencyKey,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= $attempts || ! str_contains($exception->getMessage(), 'bookings_code_unique')) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Bookings with a scheduled time wait in `draft` until the search lead time before it (ScheduledBookingReleaseService).
     */
    private function searchOpensNow(ValidatedBookingRequest $validated, CarbonImmutable $now): bool
    {
        return $validated->scheduledAt === null
            || $validated->scheduledAt->lessThanOrEqualTo($now->addMinutes($this->settings->int(SettingKey::ScheduledSearchLeadMinutes)));
    }
}
