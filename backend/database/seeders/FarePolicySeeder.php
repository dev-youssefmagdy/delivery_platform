<?php

namespace Database\Seeders;

use App\Enums\Pricing\FareFormula;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Starter EGP reference prices for the seeded catalog (SRS §9.1): guidance for customers, never a quote. These are
 * STARTING POINTS to review before launch (Fare policies in the admin panel). Idempotent and non-destructive: a
 * category that already has any policy keeps exactly what an admin configured.
 */
class FarePolicySeeder extends Seeder
{
    /**
     * code => [base, per km, per minute, minimum fare, per stop, rounding step] in piasters.
     *
     * @var array<string, array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int}>
     */
    private const STARTING_PRICES = [
        'ride_tuk_tuk' => [500, 200, 20, 1000, 300, 100],
        'ride_taxi' => [1000, 400, 50, 2000, 500, 100],
        'ride_economy' => [900, 380, 45, 1800, 500, 100],
        'ride_standard' => [1200, 450, 60, 2500, 600, 100],
        'courier_motorcycle' => [800, 300, 30, 1500, 400, 100],
        'moving_pickup_truck' => [6000, 1000, 100, 8000, 1500, 500],
        'moving_furniture_truck' => [30000, 2000, 200, 40000, 3000, 500],
        'freight_truck' => [40000, 2500, 200, 60000, 4000, 500],
    ];

    public function run(): void
    {
        foreach (self::STARTING_PRICES as $code => [$base, $perKm, $perMin, $minimum, $stop, $step]) {
            $category = ServiceCategory::query()->where('code', $code)->first();

            if ($category === null || FarePolicy::query()->where('service_category_id', $category->id)->exists()) {
                continue;
            }

            FarePolicy::query()->create([
                'service_category_id' => $category->id,
                'service_zone_id' => null,
                'formula_version' => FareFormula::V1,
                'currency' => 'EGP',
                'base_minor' => $base,
                'per_km_minor' => $perKm,
                'per_min_minor' => $perMin,
                'min_fare_minor' => $minimum,
                'stop_fee_minor' => $stop,
                'attribute_surcharges' => $this->surcharges($code),
                'rounding_step_minor' => $step,
                'range_low_bp' => 8500,
                'range_high_bp' => 12500,
                'active' => true,
                'effective_from' => '2026-01-01 00:00:00',
                'notes' => 'Starting prices: review before launch.',
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function surcharges(string $code): array
    {
        return match ($code) {
            'moving_pickup_truck' => [['field' => 'helpers', 'mode' => 'per_unit', 'amount_minor' => 5000, 'match' => null, 'label' => ['ar' => 'عمال التحميل', 'en' => 'Loading helpers']]],
            'moving_furniture_truck' => [
                ['field' => 'helpers', 'mode' => 'per_unit', 'amount_minor' => 8000, 'match' => null, 'label' => ['ar' => 'عمال التحميل', 'en' => 'Loading helpers']],
                ['field' => 'disassembly', 'mode' => 'fixed', 'amount_minor' => 15000, 'match' => null, 'label' => ['ar' => 'فك وتركيب', 'en' => 'Disassembly and assembly']],
                ['field' => 'packing', 'mode' => 'fixed', 'amount_minor' => 20000, 'match' => null, 'label' => ['ar' => 'تغليف', 'en' => 'Packing']],
            ],
            'freight_truck' => [['field' => 'needs_loaders', 'mode' => 'fixed', 'amount_minor' => 10000, 'match' => null, 'label' => ['ar' => 'عمال تحميل', 'en' => 'Loaders']]],
            default => [],
        };
    }
}
