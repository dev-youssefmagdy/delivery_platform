<?php

namespace App\DTOs\Booking;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the admin Bookings listing (Plan Appendix C): code, statuses, service category, booking type, city/zone,
 * payment status and method, created range, customer phone, provider phone, has incident. "Has ticket" arrives with
 * the support module (P3.7). Phones are matched exactly (full number) or by their last digits, never shown.
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
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
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
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->code, $this->statuses, $this->serviceCategoryIds, $this->bookingTypes, $this->cityIds, $this->zoneIds,
            $this->paymentStatuses, $this->paymentMethods, $this->from ?? $this->to, $this->customerPhone, $this->providerPhone, $this->hasIncident,
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
