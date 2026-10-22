<?php

namespace App\DTOs\Booking;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CargoType;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Booking\ParcelSize;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the admin Bookings listing (Plan Appendix C): code, statuses, service category, booking type, city/zone,
 * payment status and method, created range, customer phone, provider phone, has incident, has ticket (P3.7), custody status, parcel size and fragile (P4.1 indexed columns), cargo type, vehicle class and cargo weight range (P4.4). Phones are matched exactly (full number) or by their last digits, never shown.
 */
final readonly class BookingAdminFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<BookingStatus> $statuses
     * @param list<int> $serviceCategoryIds
     * @param list<BookingType> $bookingTypes
     * @param list<int> $cityIds
     * @param list<int> $zoneIds
     * @param list<PaymentStatus> $paymentStatuses
     * @param list<PaymentMethodType> $paymentMethods
     * @param list<CustodyStatus> $custodyStatuses
     * @param list<ParcelSize> $parcelSizes
     * @param list<CargoType> $cargoTypes
     * @param list<int> $vehicleCategoryIds vehicle classes named by the requested or accepted scope
     */
    public function __construct(
        public ?string $code = null,
        public array $statuses = [],
        public array $serviceCategoryIds = [],
        public array $bookingTypes = [],
        public array $cityIds = [],
        public array $zoneIds = [],
        public array $paymentStatuses = [],
        public array $paymentMethods = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?string $customerPhone = null,
        public ?string $providerPhone = null,
        public ?bool $hasIncident = null,
        public ?int $providerId = null,
        public ?bool $hasTicket = null,
        public array $custodyStatuses = [],
        public array $parcelSizes = [],
        public ?bool $isFragile = null,
        public array $cargoTypes = [],
        public array $vehicleCategoryIds = [],
        public ?int $weightMin = null,
        public ?int $weightMax = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$weightMin, $weightMax] = self::intRangeFilter($input, 'cargo_weight_kg');
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            code: self::stringFilter($input, 'code', 16),
            statuses: self::enumListFilter($input, 'statuses', BookingStatus::class),
            serviceCategoryIds: self::idListFilter($input, 'service_category_ids'),
            bookingTypes: self::enumListFilter($input, 'booking_types', BookingType::class),
            cityIds: self::idListFilter($input, 'city_ids'),
            zoneIds: self::idListFilter($input, 'zone_ids'),
            paymentStatuses: self::enumListFilter($input, 'payment_statuses', PaymentStatus::class),
            paymentMethods: self::enumListFilter($input, 'payment_methods', PaymentMethodType::class),
            from: $from,
            to: $to,
            customerPhone: self::stringFilter($input, 'customer_phone', 24),
            providerPhone: self::stringFilter($input, 'provider_phone', 24),
            hasIncident: self::boolFilter($input, 'has_incident'),
            hasTicket: self::boolFilter($input, 'has_ticket'),
            custodyStatuses: self::enumListFilter($input, 'custody_statuses', CustodyStatus::class),
            parcelSizes: self::enumListFilter($input, 'parcel_sizes', ParcelSize::class),
            isFragile: self::boolFilter($input, 'is_fragile'),
            cargoTypes: self::enumListFilter($input, 'cargo_types', CargoType::class),
            vehicleCategoryIds: self::idListFilter($input, 'vehicle_category_ids'),
            weightMin: $weightMin,
            weightMax: $weightMax,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->code, $this->statuses, $this->serviceCategoryIds, $this->bookingTypes, $this->cityIds, $this->zoneIds,
            $this->paymentStatuses, $this->paymentMethods, $this->from ?? $this->to, $this->customerPhone, $this->providerPhone, $this->hasIncident, $this->hasTicket,
            $this->custodyStatuses, $this->parcelSizes, $this->isFragile, $this->cargoTypes, $this->vehicleCategoryIds, $this->weightMin ?? $this->weightMax,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'statuses' => array_map(static fn (BookingStatus $status): string => $status->value, $this->statuses),
            'service_category_ids' => $this->serviceCategoryIds,
            'booking_types' => array_map(static fn (BookingType $type): string => $type->value, $this->bookingTypes),
            'city_ids' => $this->cityIds,
            'zone_ids' => $this->zoneIds,
            'payment_statuses' => array_map(static fn (PaymentStatus $status): string => $status->value, $this->paymentStatuses),
            'payment_methods' => array_map(static fn (PaymentMethodType $method): string => $method->value, $this->paymentMethods),
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'customer_phone' => $this->customerPhone,
            'provider_phone' => $this->providerPhone,
            'has_incident' => $this->hasIncident === null ? null : ($this->hasIncident ? '1' : '0'),
            'has_ticket' => $this->hasTicket === null ? null : ($this->hasTicket ? '1' : '0'),
            'custody_statuses' => array_map(static fn (CustodyStatus $status): string => $status->value, $this->custodyStatuses),
            'parcel_sizes' => array_map(static fn (ParcelSize $size): string => $size->value, $this->parcelSizes),
            'is_fragile' => $this->isFragile === null ? null : ($this->isFragile ? '1' : '0'),
            'cargo_types' => array_map(static fn (CargoType $type): string => $type->value, $this->cargoTypes),
            'vehicle_category_ids' => $this->vehicleCategoryIds,
            'cargo_weight_kg' => array_filter(['min' => $this->weightMin, 'max' => $this->weightMax], static fn (?int $value): bool => $value !== null),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
    /**
     * Bookings of one provider (the Jobs tab of the provider page); not a user filter, so it is not in the URL.
     */
    public static function forProvider(int $providerId): self
    {
        return new self(providerId: $providerId);
    }
}
