<?php

namespace App\DTOs\Catalog;

use App\Enums\Catalog\BookingType;
use App\Enums\Catalog\ProofType;
use App\Enums\Payments\PaymentMethodType;
use App\Http\Requests\Admin\Catalog\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\Catalog\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;

/**
 * Validated input for creating/updating a service category's draft configuration. Null policy fields mean
 * "use the platform setting" (CategoryPolicyResolver).
 */
final readonly class ServiceCategoryData
{
    /**
     * @param array<string, string|null> $name
     * @param array<string, string|null> $description
     * @param list<BookingType> $allowedBookingTypes
     * @param list<float>|null $dispatchRadiusStepsKm
     * @param list<ProofType> $deliveryProofTypes
     * @param list<PaymentMethodType> $paymentMethods
     * @param list<array{day: int, from: string, to: string}> $serviceHours
     */
    public function __construct(
        public int $serviceFamilyId,
        public string $code,
        public array $name,
        public array $description,
        public ?string $icon,
        public ?string $mapMarker,
        public int $sortOrder,
        public array $allowedBookingTypes,
        public array $serviceHours,
        public bool $negotiationEnabled,
        public bool $referenceEstimateEnabled,
        public ?int $minCustomerFareMinor,
        public ?int $maxCustomerFareMinor,
        public ?int $counterOfferMinPct,
        public ?int $counterOfferMaxPct,
        public ?int $offerTtlSeconds,
        public ?int $searchTtlSeconds,
        public ?array $dispatchRadiusStepsKm,
        public ?int $dispatchBatchSize,
        public ?int $maxPassengers,
        public ?int $maxLuggage,
        public ?string $maxWeightKg,
        public ?string $maxVolumeM3,
        public bool $supportsStops,
        public int $maxStops,
        public ?bool $pinRequired,
        public bool $pickupProofRequired,
        public array $deliveryProofTypes,
        public bool $recipientOtpRequired,
        public bool $tippingEnabled,
        public bool $couponEligible,
        public bool $cashAllowed,
        public array $paymentMethods,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreServiceCategoryRequest|UpdateServiceCategoryRequest $request): self
    {
        /** @var array<string, mixed> $d */
        $d = $request->validated();
        $current = $request->route('serviceCategory');
        $text = static fn (string $key): ?string => ($value = trim((string) ($d[$key] ?? ''))) === '' ? null : $value;
        $int = static fn (string $key): ?int => isset($d[$key]) && $d[$key] !== '' ? (int) $d[$key] : null;
        $bool = static fn (string $key): bool => filter_var($d[$key] ?? false, FILTER_VALIDATE_BOOLEAN);

        $steps = $text('dispatch_radius_steps_km');
        $hours = [];

        foreach ((array) ($d['service_hours'] ?? []) as $row) {
            if (is_array($row)) {
                $hours[] = ['day' => (int) $row['day'], 'from' => (string) $row['from'], 'to' => (string) $row['to']];
            }
        }

        usort($hours, static fn (array $a, array $b): int => [$a['day'], $a['from']] <=> [$b['day'], $b['from']]);

        $pin = $d['pin_required'] ?? null;

        return new self(
            serviceFamilyId: (int) $d['service_family_id'],
            code: strtolower(trim((string) ($d['code'] ?? ($current instanceof ServiceCategory ? $current->code : '')))),
            name: FormFieldDefinition::translations($d['name'] ?? []),
            description: FormFieldDefinition::translations($d['description'] ?? []),
            icon: $text('icon'),
            mapMarker: $text('map_marker'),
            sortOrder: (int) ($d['sort_order'] ?? 0),
            allowedBookingTypes: array_values(array_map(static fn (mixed $v): BookingType => BookingType::from((string) $v), (array) ($d['allowed_booking_types'] ?? []))),
            serviceHours: $hours,
            negotiationEnabled: $bool('negotiation_enabled'),
            referenceEstimateEnabled: $bool('reference_estimate_enabled'),
            minCustomerFareMinor: $int('min_customer_fare_minor'),
            maxCustomerFareMinor: $int('max_customer_fare_minor'),
            counterOfferMinPct: $int('counter_offer_min_pct'),
            counterOfferMaxPct: $int('counter_offer_max_pct'),
            offerTtlSeconds: $int('offer_ttl_seconds'),
            searchTtlSeconds: $int('search_ttl_seconds'),
            dispatchRadiusStepsKm: $steps === null ? null : array_map(static fn (string $step): float => (float) trim($step), explode(',', $steps)),
            dispatchBatchSize: $int('dispatch_batch_size'),
            maxPassengers: $int('max_passengers'),
            maxLuggage: $int('max_luggage'),
            maxWeightKg: $text('max_weight_kg'),
            maxVolumeM3: $text('max_volume_m3'),
            supportsStops: $bool('supports_stops'),
            maxStops: $bool('supports_stops') ? (int) ($d['max_stops'] ?? 0) : 0,
            pinRequired: $pin === null || $pin === '' ? null : filter_var($pin, FILTER_VALIDATE_BOOLEAN),
            pickupProofRequired: $bool('pickup_proof_required'),
            deliveryProofTypes: array_values(array_map(static fn (mixed $v): ProofType => ProofType::from((string) $v), (array) ($d['delivery_proof_types'] ?? []))),
            recipientOtpRequired: $bool('recipient_otp_required'),
            tippingEnabled: $bool('tipping_enabled'),
            couponEligible: $bool('coupon_eligible'),
            cashAllowed: $bool('cash_allowed'),
            paymentMethods: array_values(array_map(static fn (mixed $v): PaymentMethodType => PaymentMethodType::from((string) $v), (array) ($d['payment_methods'] ?? []))),
            lockVersion: isset($d['lock_version']) ? (int) $d['lock_version'] : null,
        );
    }

    /**
     * Model attributes (active, published version and lock_version are managed by the services).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'service_family_id' => $this->serviceFamilyId,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description === [] ? null : $this->description,
            'icon' => $this->icon,
            'map_marker' => $this->mapMarker,
            'sort_order' => $this->sortOrder,
            'allowed_booking_types' => array_map(static fn (BookingType $type): string => $type->value, $this->allowedBookingTypes),
            'service_hours' => $this->serviceHours === [] ? null : $this->serviceHours,
            'negotiation_enabled' => $this->negotiationEnabled,
            'reference_estimate_enabled' => $this->referenceEstimateEnabled,
            'min_customer_fare_minor' => $this->minCustomerFareMinor,
            'max_customer_fare_minor' => $this->maxCustomerFareMinor,
            'counter_offer_min_pct' => $this->counterOfferMinPct,
            'counter_offer_max_pct' => $this->counterOfferMaxPct,
            'offer_ttl_seconds' => $this->offerTtlSeconds,
            'search_ttl_seconds' => $this->searchTtlSeconds,
            'dispatch_radius_steps_km' => $this->dispatchRadiusStepsKm,
            'dispatch_batch_size' => $this->dispatchBatchSize,
            'max_passengers' => $this->maxPassengers,
            'max_luggage' => $this->maxLuggage,
            'max_weight_kg' => $this->maxWeightKg,
            'max_volume_m3' => $this->maxVolumeM3,
            'supports_stops' => $this->supportsStops,
            'max_stops' => $this->maxStops,
            'pin_required' => $this->pinRequired,
            'pickup_proof_required' => $this->pickupProofRequired,
            'delivery_proof_types' => array_map(static fn (ProofType $type): string => $type->value, $this->deliveryProofTypes),
            'recipient_otp_required' => $this->recipientOtpRequired,
            'tipping_enabled' => $this->tippingEnabled,
            'coupon_eligible' => $this->couponEligible,
            'cash_allowed' => $this->cashAllowed,
            'payment_methods' => array_map(static fn (PaymentMethodType $type): string => $type->value, $this->paymentMethods),
        ];
    }
}
