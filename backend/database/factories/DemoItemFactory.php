<?php

namespace Database\Factories;

use App\Enums\Demo\DemoItemStatus;
use App\Enums\Demo\DemoItemType;
use App\Models\Currency;
use App\Models\DemoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemoItem>
 */
class DemoItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['ar' => 'عنصر '.fake()->numberBetween(1, 9999), 'en' => fake()->words(3, true)],
            'code' => strtoupper(fake()->unique()->bothify('DEMO-####??')),
            'type' => DemoItemType::Standard,
            'status' => DemoItemStatus::Draft,
            'price_minor' => fake()->numberBetween(100, 100_000),
            'currency' => fn (): string => Currency::query()->where('code', 'EGP')->value('code')
                ?? Currency::factory()->create(['code' => 'EGP', 'exponent' => 2])->code,
            'is_featured' => false,
            'is_public' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
