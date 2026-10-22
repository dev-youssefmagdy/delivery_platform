<?php

namespace App\DTOs\Booking;

use App\DTOs\Geography\CategoryAvailability;
use App\DTOs\Geography\ResolvedArea;
use App\DTOs\Geography\RouteData;
use App\DTOs\Pricing\PriceGuidanceResult;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * A booking request that passed every server-side rule, with everything the snapshots are built from. It exists only
 * between the validator and the assembler; nothing in it came from the client except the choices that were checked.
 */
final readonly class ValidatedBookingRequest
{
    /**
     * @param list<BookingLocationData> $stops
     * @param array<string, mixed> $serviceDetails normalised to the form schema (known, visible fields only)
     * @param array{min: int|null, max: int|null, source: string} $fareBounds
     */
    public function __construct(
        public ServiceCategory $category,
        public ServiceCategoryVersion $version,
        public ResolvedArea $area,
        public CategoryAvailability $availability,
        public BookingType $bookingType,
        public BookingLocationData $pickup,
        public ?BookingLocationData $dropoff,
        public array $stops,
        public ?CarbonImmutable $scheduledAt,
        public ?int $passengers,
        public ?int $luggageCount,
        public ?string $cargoWeightKg,
        public array $serviceDetails,
        public Money $proposedFare,
        public PaymentMethodType $paymentMethod,
        public ?RouteData $route,
        public PriceGuidanceResult $guidance,
        public ?FarePolicy $farePolicy,
        public array $fareBounds,
        public CarbonImmutable $validatedAt,
        /** @var array<string, array{name: string, phone: string}> parcel contacts pulled out of the service details (role => contact) */
        public array $contacts = [],
        /** @var array<string, mixed> category-specific searchable columns (see ParcelColumns) */
        public array $columns = [],
        public ?int $restrictedGoodsPolicyVersionId = null,
    ) {}
}
