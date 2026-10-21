<?php

namespace Tests\Concerns;

use App\Enums\Catalog\BookingType;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\Catalog\ServiceCategoryPublisher;
use App\Services\Catalog\ServiceFormSchemaService;
use Database\Seeders\CurrencySeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Fixtures for booking tests: a ride category with a real published form schema (passengers, luggage, notes, an
 * optional "child seat" toggle), an active Cairo zone offering it, a fare policy, a customer and a valid payload.
 * The default zone is the 30.0..30.1 N, 31.2..31.3 E square; pickup (30.05, 31.25), drop-off (30.07, 31.27).
 */
trait BuildsBookings
{
    use BuildsCustomers;
    use BuildsDispatch;

    protected function seedBookingReferenceData(): void
    {
        $this->seed(CurrencySeeder::class);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function rideCategory(array $attributes = []): ServiceCategory
    {
        $category = ServiceCategory::factory()->create($attributes + [
            'allowed_booking_types' => [BookingType::OnDemand->value, BookingType::Scheduled->value],
            'min_customer_fare_minor' => 1500,
            'max_customer_fare_minor' => 150000,
            'max_passengers' => 4,
            'max_luggage' => 2,
            'supports_stops' => true,
            'max_stops' => 2,
            'cash_allowed' => true,
            'payment_methods' => ['cash', 'card'],
        ]);
        $category->vehicleCategories()->attach(VehicleCategory::factory()->create(['passenger_capacity' => 4]));

        $draft = app(ServiceFormSchemaService::class)->draftFor($category);
        $draft->fields = [
            $this->schemaField('passengers', 'integer', required: true, min: 1, max: 4),
            $this->schemaField('luggage', 'integer', min: 0, max: 2),
            $this->schemaField('child_seat', 'boolean'),
            $this->schemaField('notes', 'textarea', max: 300),
        ];
        $draft->save();

        app(ServiceCategoryPublisher::class)->publish($category, 'Release', null);
        $category->refresh()->forceFill(['active' => true])->save();

        return $category->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    protected function schemaField(string $key, string $type, bool $required = false, int|float|null $min = null, int|float|null $max = null): array
    {
        return [
            'key' => $key, 'type' => $type, 'label' => ['ar' => $key, 'en' => $key], 'help' => null, 'required' => $required,
            'min' => $min, 'max' => $max, 'options' => [], 'visible_if' => null,
        ];
    }

    /**
     * @return array{category: ServiceCategory, zone: ServiceZone, customer: User, policy: FarePolicy}
     */
    protected function bookingWorld(): array
    {
        $this->seedBookingReferenceData();
        $category = $this->rideCategory();
        $zone = $this->zoneOffering([$category]);
        $policy = FarePolicy::factory()->create(['service_category_id' => $category->id]);
        $customer = $this->customer();

        return ['category' => $category, 'zone' => $zone, 'customer' => $customer, 'policy' => $policy];
    }

    protected function actingAsCustomer(User $customer): User
    {
        Sanctum::actingAs($customer->refresh(), ['customer']);

        return $customer;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function bookingPayload(ServiceCategory $category, array $overrides = []): array
    {
        return $overrides + [
            'service_category_id' => $category->id,
            'booking_type' => 'on_demand',
            'pickup' => ['lat' => 30.05, 'lng' => 31.25, 'address' => ['title' => 'Home', 'line' => '1 Pickup St']],
            'dropoff' => ['lat' => 30.07, 'lng' => 31.27, 'address' => ['title' => 'Work', 'line' => '2 Dropoff St']],
            'passengers' => 2,
            'service_details' => ['luggage' => 1],
            'proposed_fare_minor' => 5000,
            'payment_method' => 'cash',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function idempotency(?string $key = null): array
    {
        return ['Idempotency-Key' => $key ?? (string) \Illuminate\Support\Str::uuid()];
    }
}
