<?php

namespace Database\Factories;

use App\Enums\Geo\ZoneStatus;
use App\Models\City;
use App\Models\ServiceZone;
use App\Support\Geo\GeoPolygon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default polygon: a 0.1 x 0.1 degree square around downtown Cairo (30.0..30.1 N, 31.2..31.3 E).
 *
 * @extends Factory<ServiceZone>
 */
class ServiceZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => ['ar' => 'منطقة '.fake()->unique()->numerify('####'), 'en' => 'Zone '.fake()->unique()->numerify('####')],
            'polygon' => self::square(30.0, 31.2, 0.1),
            'status' => ZoneStatus::Active,
            'operating_hours' => null,
            'restricted_pickup' => false,
        ];
    }

    public static function square(float $south, float $west, float $size): GeoPolygon
    {
        return GeoPolygon::fromLatLngList([
            [$south, $west], [$south, $west + $size], [$south + $size, $west + $size], [$south + $size, $west],
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => ZoneStatus::Draft]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => ZoneStatus::Suspended]);
    }

    public function restricted(): static
    {
        return $this->state(['restricted_pickup' => true]);
    }
}
