<?php

namespace App\Services\Coupon;

use App\DTOs\Booking\ValidatedBookingRequest;
use App\DTOs\Coupon\CouponContext;
use App\Enums\Payments\PaymentMethodType;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Money\Money;
use Carbon\CarbonInterface;

/**
 * Assembles the server-side facts a coupon is evaluated against. Nothing here comes from a client discount: the customer's
 * history, the service, the area and the fare are read or validated by the server.
 */
final class CouponContextBuilder
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    /**
     * The context of a booking being created. The fare is the highest the trip can cost under the fare bounds (never below the
     * proposed fare), so the budget hold is a ceiling: the real discount is computed on the agreed fare and never exceeds it.
     */
    public function forBooking(int $customerId, ValidatedBookingRequest $request): CouponContext
    {
        $bound = (int) ($request->fareBounds['max'] ?? 0);
        $fare = $request->proposedFare->max(Money::of($bound, $request->proposedFare->currency));

        return $this->build(
            $customerId,
            $request->category,
            $request->version,
            $request->area->city?->id,
            $request->area->zone?->id,
            $request->paymentMethod,
            $fare,
            $request->scheduledAt ?? $request->validatedAt,
            (int) ($request->serviceDetails['vehicle_category_id'] ?? 0) ?: null,
        );
    }

    public function build(
        ?int $customerId,
        ServiceCategory $category,
        ?ServiceCategoryVersion $version,
        ?int $cityId,
        ?int $zoneId,
        ?PaymentMethodType $paymentMethod,
        Money $fare,
        CarbonInterface $at,
        ?int $chosenVehicleCategoryId = null,
    ): CouponContext {
        $vehicles = array_values(array_map(static fn (array $vehicle): int => (int) $vehicle['id'], (array) ($version?->snapshot['vehicle_categories'] ?? [])));

        if ($chosenVehicleCategoryId !== null && in_array($chosenVehicleCategoryId, $vehicles, true)) {
            $vehicles = [$chosenVehicleCategoryId];
        }

        return new CouponContext(
            userId: $customerId,
            isNewCustomer: $customerId === null || $this->bookings->completedCountForCustomer($customerId) === 0,
            customerUses: 0,
            serviceFamilyId: $category->service_family_id,
            serviceCategoryId: $category->id,
            categoryAllowsCoupons: $category->coupon_eligible,
            vehicleCategoryIds: $vehicles,
            cityId: $cityId,
            zoneId: $zoneId,
            paymentMethod: $paymentMethod,
            fare: $fare,
            at: $at,
        );
    }
}
