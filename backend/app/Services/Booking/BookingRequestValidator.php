<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingLocationData;
use App\DTOs\Booking\CreateBookingData;
use App\DTOs\Booking\ValidatedBookingRequest;
use App\DTOs\Geography\CategoryAvailability;
use App\DTOs\Geography\RouteData;
use App\DTOs\Pricing\PriceGuidanceResult;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Services\Catalog\Forms\ServiceFormValidator;
use App\Services\Geography\GeoService;
use App\Services\Geography\MapsService;
use App\Services\Pricing\PriceGuidanceService;
use App\Services\Settings\SettingsService;
use App\Support\Money\Currency;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Every server-side rule a booking request must pass (SRS §6.4, Plan P2.3 step 4): zone and category availability at
 * the time of the trip, booking type, schedule window, stops, capacity, the category's form schema, fare bounds,
 * payment method and the route. Rules read the category's immutable published version (or the booking's own version
 * when an existing request is edited), never the editable draft. Nothing here trusts a client total: the reference
 * estimate is computed by the server and the proposed fare is only compared with configured bounds.
 */
final class BookingRequestValidator
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly MapsService $maps,
        private readonly ServiceFormValidator $forms,
        private readonly PriceGuidanceService $pricing,
        private readonly SettingsService $settings,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly CurrencyRegistry $currencies,
    ) {}

    /**
     * @param ServiceCategoryVersion|null $pinnedVersion the booking's own version when an existing request is edited
     *
     * @throws BusinessRuleViolation with the stable code of the rule that failed
     */
    public function validate(CreateBookingData $data, CarbonImmutable $now, ?ServiceCategoryVersion $pinnedVersion = null): ValidatedBookingRequest
    {
        $category = $this->categories->find($data->serviceCategoryId);

        if (! $category instanceof ServiceCategory) {
            throw BusinessRuleViolation::make('category_unavailable', 'service_category_id');
        }

        $at = $data->scheduledAt ?? $now;
        $area = $this->geo->resolve($data->pickup->point, $at);

        if (! $area->inServiceArea()) {
            throw BusinessRuleViolation::make('outside_service_area', 'pickup');
        }

        $availability = $area->availability($category->id);

        if (! $availability instanceof CategoryAvailability || ! $availability->isAvailable()) {
            throw BusinessRuleViolation::make('category_unavailable', 'service_category_id');
        }

        $version = $pinnedVersion ?? $category->publishedVersion;

        if (! $version instanceof ServiceCategoryVersion) {
            throw BusinessRuleViolation::make('category_unavailable', 'service_category_id');
        }

        /** @var array<string, mixed> $snapshot */
        $snapshot = $version->snapshot;

        $this->assertBookingType($data->bookingType, $snapshot);
        $this->assertSchedule($data, $now);
        $this->assertPlaces($data, $snapshot);

        [$details, $passengers] = $this->validateDetails($data, $snapshot);
        $luggage = isset($details['luggage']) && is_numeric($details['luggage']) ? (int) $details['luggage'] : null;
        $weight = $this->weightKg($snapshot, $details);
        $this->assertCapacity($passengers, $luggage, $weight, $snapshot);

        $currency = $this->currency($data);
        $fare = Money::of($data->proposedFareMinor, $currency);
        $bounds = $this->assertFareBounds($fare, $snapshot, $availability);
        $method = $this->assertPaymentMethod($data->paymentMethod, $snapshot);

        $route = $this->route($data);
        $policy = $this->pricing->policyFor($category, $area->zone?->id, $at);
        $guidance = $route === null
            ? PriceGuidanceResult::unavailable(PriceGuidanceResult::NO_ROUTE)
            : $this->pricing->guideWith($category, $policy, $route, count($data->stops), $details);

        return new ValidatedBookingRequest(
            category: $category,
            version: $version,
            area: $area,
            availability: $availability,
            bookingType: $data->bookingType,
            pickup: $data->pickup,
            dropoff: $data->dropoff,
            stops: $data->stops,
            scheduledAt: $data->scheduledAt,
            passengers: $passengers,
            luggageCount: $luggage,
            cargoWeightKg: $weight,
            serviceDetails: $details,
            proposedFare: $fare,
            paymentMethod: $method,
            route: $route,
            guidance: $guidance,
            farePolicy: $guidance->guidance === null ? null : $policy,
            fareBounds: $bounds,
            validatedAt: $now,
        );
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function assertBookingType(BookingType $type, array $snapshot): void
    {
        if (! in_array($type->value, (array) ($snapshot['booking']['allowed_booking_types'] ?? []), true)) {
            throw BusinessRuleViolation::make('booking_type_not_allowed', 'booking_type');
        }
    }

    /**
     * On-demand bookings start now; scheduled ones need a time inside the configured window; the other types may
     * carry a time inside the same window.
     */
    private function assertSchedule(CreateBookingData $data, CarbonImmutable $now): void
    {
        if ($data->bookingType === BookingType::OnDemand) {
            if ($data->scheduledAt !== null) {
                throw BusinessRuleViolation::make('scheduled_at_not_allowed', 'scheduled_at');
            }

            return;
        }

        if ($data->scheduledAt === null) {
            if ($data->bookingType === BookingType::Scheduled) {
                throw BusinessRuleViolation::make('scheduled_at_required', 'scheduled_at');
            }

            return;
        }

        $earliest = $now->addMinutes($this->settings->int(SettingKey::ScheduledBookingMinLeadMinutes));
        $latest = $now->addDays($this->settings->int(SettingKey::ScheduledBookingMaxDays));

        if ($data->scheduledAt->lessThan($earliest)) {
            throw BusinessRuleViolation::make('scheduled_too_soon', 'scheduled_at', ['minutes' => $this->settings->int(SettingKey::ScheduledBookingMinLeadMinutes)]);
        }

        if ($data->scheduledAt->greaterThan($latest)) {
            throw BusinessRuleViolation::make('scheduled_too_far', 'scheduled_at', ['days' => $this->settings->int(SettingKey::ScheduledBookingMaxDays)]);
        }
    }

    /**
     * The drop-off is required (except for hourly hire) and must be inside a service zone unless the trip is
     * intercity or freight; stops need a category that supports them.
     *
     * @param array<string, mixed> $snapshot
     */
    private function assertPlaces(CreateBookingData $data, array $snapshot): void
    {
        if ($data->dropoff === null) {
            if ($data->bookingType !== BookingType::Hourly) {
                throw BusinessRuleViolation::make('dropoff_required', 'dropoff');
            }
        } elseif (! in_array($data->bookingType, [BookingType::Intercity, BookingType::Freight], true) && ! $this->geo->resolve($data->dropoff->point, $data->scheduledAt)->inServiceArea()) {
            throw BusinessRuleViolation::make('dropoff_outside_service_area', 'dropoff');
        }

        if ($data->stops === []) {
            return;
        }

        if (! (bool) ($snapshot['booking']['supports_stops'] ?? false)) {
            throw BusinessRuleViolation::make('stops_not_supported', 'stops');
        }

        $max = (int) ($snapshot['booking']['max_stops'] ?? 0);

        if (count($data->stops) > $max) {
            throw BusinessRuleViolation::make('too_many_stops', 'stops', ['max' => $max]);
        }
    }

    /**
     * Validates `service_details` against the version's schema. Passengers may be sent at top level; when the schema
     * also asks for them the two must agree and the top-level value fills the answer.
     *
     * @param array<string, mixed> $snapshot
     * @return array{0: array<string, mixed>, 1: int|null}
     */
    private function validateDetails(CreateBookingData $data, array $snapshot): array
    {
        /** @var list<array<string, mixed>> $fields */
        $fields = (array) ($snapshot['form_schema']['fields'] ?? []);
        $keys = array_map(static fn (array $field): string => (string) ($field['key'] ?? ''), $fields);
        $details = $data->serviceDetails;
        $passengers = $data->passengers;

        if (in_array('passengers', $keys, true)) {
            if ($passengers !== null && isset($details['passengers']) && (int) $details['passengers'] !== $passengers) {
                throw BusinessRuleViolation::make('passengers_mismatch', 'passengers');
            }

            if ($passengers !== null) {
                $details['passengers'] = $passengers;
            }
        }

        $validated = $this->forms->validate($fields, $details);
        $passengers ??= isset($validated['passengers']) && is_numeric($validated['passengers']) ? (int) $validated['passengers'] : null;

        if ($passengers === null && ($snapshot['limits']['max_passengers'] ?? null) !== null) {
            throw BusinessRuleViolation::make('passengers_required', 'passengers');
        }

        return [$validated, $passengers];
    }

    /**
     * The first answered weight field of the schema, as a decimal string (kg).
     *
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $details
     */
    private function weightKg(array $snapshot, array $details): ?string
    {
        foreach ((array) ($snapshot['form_schema']['fields'] ?? []) as $field) {
            if (($field['type'] ?? null) === 'weight' && isset($details[$field['key']]) && is_numeric($details[$field['key']])) {
                return number_format((float) $details[$field['key']], 2, '.', '');
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function assertCapacity(?int $passengers, ?int $luggage, ?string $weight, array $snapshot): void
    {
        $limits = (array) ($snapshot['limits'] ?? []);
        $vehicles = (array) ($snapshot['vehicle_categories'] ?? []);

        if ($passengers !== null) {
            $vehicleMax = $vehicles === [] ? 0 : max(array_map(static fn (array $v): int => (int) ($v['passenger_capacity'] ?? 0), $vehicles));
            $caps = array_filter([$limits['max_passengers'] ?? null, $vehicleMax > 0 ? $vehicleMax : null]);

            if ($passengers < 1 || ($caps !== [] && $passengers > min($caps))) {
                throw BusinessRuleViolation::make('capacity_exceeded', 'passengers', ['max' => $caps === [] ? null : min($caps)]);
            }
        }

        if ($luggage !== null && ($limits['max_luggage'] ?? null) !== null && $luggage > (int) $limits['max_luggage']) {
            throw BusinessRuleViolation::make('luggage_exceeded', 'service_details.luggage', ['max' => (int) $limits['max_luggage']]);
        }

        if ($weight !== null) {
            $payloads = array_filter(array_map(static fn (array $v): float => (float) ($v['max_payload_kg'] ?? 0), $vehicles));
            $caps = array_filter([isset($limits['max_weight_kg']) ? (float) $limits['max_weight_kg'] : null, $payloads === [] ? null : max($payloads)]);

            if ($caps !== [] && (float) $weight > min($caps)) {
                throw BusinessRuleViolation::make('weight_exceeded', 'service_details', ['max' => min($caps)]);
            }
        }
    }

    private function currency(CreateBookingData $data): Currency
    {
        $market = (string) $this->settings->string(SettingKey::DefaultCurrency);

        if ($data->currency !== null && strtoupper($data->currency) !== $market) {
            throw BusinessRuleViolation::make('currency_mismatch', 'currency', ['currency' => $market]);
        }

        return $this->currencies->get($market);
    }

    /**
     * Customer fare bounds: a zone override beats the category's value; with no maximum the platform ceiling applies.
     *
     * @param array<string, mixed> $snapshot
     * @return array{min: int|null, max: int|null, source: string}
     */
    private function assertFareBounds(Money $fare, array $snapshot, CategoryAvailability $availability): array
    {
        $pricing = (array) ($snapshot['pricing'] ?? []);
        $zoneMin = $availability->overrides['min_fare_minor'] ?? null;
        $zoneMax = $availability->overrides['max_fare_minor'] ?? null;
        $min = $zoneMin ?? ($pricing['min_customer_fare_minor'] ?? null);
        $max = $zoneMax ?? ($pricing['max_customer_fare_minor'] ?? null);
        $source = $zoneMin !== null || $zoneMax !== null ? 'zone' : (($min !== null || $max !== null) ? 'category' : 'platform');

        $min = $min === null ? null : (int) $min;
        $max = $max === null ? (int) config('booking.hard_max_fare_minor') : (int) $max;

        if ($fare->amount <= 0 || ($min !== null && $fare->amount < $min)) {
            throw BusinessRuleViolation::make('fare_below_minimum', 'proposed_fare_minor', ['min' => $min ?? 1]);
        }

        if ($fare->amount > $max) {
            throw BusinessRuleViolation::make('fare_above_maximum', 'proposed_fare_minor', ['max' => $max]);
        }

        return ['min' => $min, 'max' => $max, 'source' => $source];
    }

    /**
     * The customer may only pick a method the category accepts, that is enabled platform-wide today and (for cash) allowed.
     *
     * @param array<string, mixed> $snapshot
     */
    private function assertPaymentMethod(PaymentMethodType $method, array $snapshot): PaymentMethodType
    {
        $payments = (array) ($snapshot['payments'] ?? []);
        $accepted = in_array($method->value, (array) ($payments['payment_methods'] ?? []), true)
            && in_array($method->value, (array) config('booking.enabled_payment_methods', []), true)
            && ($method !== PaymentMethodType::Cash || (bool) ($payments['cash_allowed'] ?? true));

        if (! $accepted) {
            throw BusinessRuleViolation::make('payment_method_unavailable', 'payment_method');
        }

        return $method;
    }

    private function route(CreateBookingData $data): ?RouteData
    {
        if (! $data->dropoff instanceof BookingLocationData) {
            return null;
        }

        return $this->maps->route(
            $data->pickup->point,
            $data->dropoff->point,
            array_map(static fn (BookingLocationData $stop) => $stop->point, $data->stops),
        );
    }
}
