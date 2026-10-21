<?php

namespace Database\Factories;

use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\CustomerProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\Catalog\ServiceCategoryPublisher;
use App\Support\Geo\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * A booking row as the real service would have stored it, without going through the service: for tests that need a
 * booking in a given status. Defaults: on-demand ride in the default Cairo square, 50.00 EGP, cash, searching.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    private ?ServiceCategory $category = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'code' => 'BK-'.strtoupper(Str::random(6)),
            'customer_id' => function (): int {
                $user = User::factory()->create();
                CustomerProfile::query()->create(['user_id' => $user->id]);

                return $user->id;
            },
            'service_category_id' => fn (): int => $this->defaultCategory()->id,
            'service_category_version_id' => fn (): int => (int) $this->defaultCategory()->published_version_id,
            'city_id' => City::factory(),
            'service_zone_id' => null,
            'booking_type' => BookingType::OnDemand,
            'status' => BookingStatus::SearchingOffers,
            'sequence' => 0,
            'request_version' => 1,
            'pickup_point' => GeoPoint::make(30.05, 31.25),
            'pickup_address' => ['line' => 'Pickup street'],
            'dropoff_point' => GeoPoint::make(30.07, 31.27),
            'dropoff_address' => ['line' => 'Dropoff street'],
            'passengers' => 1,
            'service_details' => [],
            'proposed_fare_minor' => 5000,
            'currency' => 'EGP',
            'reference_estimate' => null,
            'route_snapshot' => ['distance_m' => 5000, 'duration_s' => 900, 'polyline' => '', 'provider' => 'test'],
            'service_snapshot' => fn (): array => ['category' => ['id' => $this->defaultCategory()->id, 'code' => $this->defaultCategory()->code, 'name' => $this->defaultCategory()->getTranslations('name')]],
            'policy_snapshot' => ['dispatch' => ['search_ttl_seconds' => 300]],
            'payment_method' => PaymentMethodType::Cash,
            'payment_status' => PaymentStatus::Pending,
            'search_expires_at' => now()->addMinutes(5),
        ];
    }

    public function forCategory(ServiceCategory $category): static
    {
        return $this->state(fn (): array => [
            'service_category_id' => $category->id,
            'service_category_version_id' => $category->published_version_id,
            'service_snapshot' => ['category' => ['id' => $category->id, 'code' => $category->code, 'name' => $category->getTranslations('name')]],
        ]);
    }

    public function forCustomer(User $customer): static
    {
        return $this->state(['customer_id' => $customer->id]);
    }

    public function inZone(ServiceZone $zone): static
    {
        return $this->state(['service_zone_id' => $zone->id, 'city_id' => $zone->city_id]);
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    private function defaultCategory(): ServiceCategory
    {
        if ($this->category === null) {
            $category = ServiceCategory::factory()->create();
            $category->vehicleCategories()->attach(VehicleCategory::factory()->create());
            app(ServiceCategoryPublisher::class)->publish($category, 'Factory release', null);
            $this->category = $category->refresh();
        }

        return $this->category;
    }
}
