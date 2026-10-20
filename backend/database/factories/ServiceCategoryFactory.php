<?php

namespace Database\Factories;

use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\ServiceFormSchema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates an unpublished, inactive category. Use ServiceCategoryPublisher (or the `published()` helpers in
 * tests) to give it a version.
 *
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_family_id' => ServiceFamily::factory(),
            'code' => 'category_'.fake()->unique()->bothify('????##'),
            'name' => ['ar' => 'فئة '.fake()->unique()->numerify('####'), 'en' => 'Category '.fake()->unique()->numerify('####')],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'icon' => 'car',
            'map_marker' => 'car',
            'sort_order' => fake()->numberBetween(0, 100),
            'active' => false,
            'allowed_booking_types' => [BookingType::OnDemand->value],
            'delivery_proof_types' => [],
            'payment_methods' => [PaymentMethodType::Cash->value],
            'max_passengers' => 4,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(static function (ServiceCategory $category): void {
            ServiceFormSchema::query()->create(['service_category_id' => $category->id, 'fields' => [], 'status' => 'draft']);
        });
    }

    public function active(): static
    {
        return $this->state(['active' => true]);
    }
}
