<?php

namespace App\DTOs\Booking;

use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use Carbon\CarbonImmutable;

/**
 * Everything a customer decides when requesting a service (SRS §6.4), as received. It never carries totals,
 * statuses, snapshots or provider data: those are produced by the server (Plan I12).
 */
final readonly class CreateBookingData
{
    /**
     * @param list<BookingLocationData> $stops
     * @param array<string, mixed> $serviceDetails
     */
    public function __construct(
        public int $serviceCategoryId,
        public BookingType $bookingType,
        public BookingLocationData $pickup,
        public ?BookingLocationData $dropoff,
        public array $stops,
        public ?CarbonImmutable $scheduledAt,
        public ?int $passengers,
        public array $serviceDetails,
        public int $proposedFareMinor,
        public ?string $currency,
        public PaymentMethodType $paymentMethod,
        public ?string $idempotencyKey = null,
        public ?string $couponCode = null,
    ) {}
}
