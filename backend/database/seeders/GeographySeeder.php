<?php

namespace Database\Seeders;

use App\Enums\Geo\ZoneStatus;
use App\Models\City;
use App\Models\Country;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Geography\ZoneServiceCategoryRepositoryInterface;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\GeoPolygon;
use Illuminate\Database\Seeder;

/**
 * DEMO geography: Cairo and Giza with one rough demo zone each, every active category enabled. The zones are
 * flagged `is_demo`, labelled "(demo)" / "(تجريبية)" and carry a notes line saying so: they are NOT real
 * operating areas and must be replaced by operations before launch. Skipped when GEO_SEED_DEMO is false
 * (default false in production). Idempotent: existing demo rows are left untouched.
 */
class GeographySeeder extends Seeder
{
    public function run(): void
    {
        if (! config('geo.seed_demo')) {
            return;
        }

        $egypt = Country::query()->where('iso2', 'EG')->first();

        if ($egypt === null) {
            return;
        }

        $categoryIds = ServiceCategory::query()->where('active', true)->pluck('id')->all();
        $links = app(ZoneServiceCategoryRepositoryInterface::class);

        foreach ($this->definitions() as $definition) {
            $city = City::query()->where('name->en', $definition['city_en'])->where('country_id', $egypt->id)->first()
                ?? City::query()->create([
                    'country_id' => $egypt->id,
                    'name' => ['ar' => $definition['city_ar'], 'en' => $definition['city_en']],
                    'timezone' => 'Africa/Cairo',
                    'center' => GeoPoint::make(...$definition['center']),
                    'active' => true,
                ]);

            $zone = ServiceZone::query()->where('city_id', $city->id)->where('is_demo', true)->first();

            if ($zone !== null) {
                continue;
            }

            $zone = ServiceZone::query()->create([
                'city_id' => $city->id,
                'name' => ['ar' => $definition['zone_ar'].' (تجريبية)', 'en' => $definition['zone_en'].' (demo)'],
                'polygon' => GeoPolygon::fromLatLngList($definition['polygon']),
                'status' => ZoneStatus::Draft,
                'notes' => 'DEMO DATA: rough rectangle for development and demos. Not a real operating area.',
                'is_demo' => true,
            ]);

            $links->enableAll($zone, $categoryIds);
            $zone->forceFill(['status' => $categoryIds === [] ? ZoneStatus::Draft : ZoneStatus::Active])->save();
        }
    }

    /**
     * @return list<array{city_ar: string, city_en: string, center: array{0: float, 1: float}, zone_ar: string, zone_en: string, polygon: list<array{0: float, 1: float}>}>
     */
    private function definitions(): array
    {
        return [
            [
                'city_ar' => 'القاهرة', 'city_en' => 'Cairo', 'center' => [30.0444, 31.2357],
                'zone_ar' => 'وسط القاهرة', 'zone_en' => 'Central Cairo',
                'polygon' => [[30.00, 31.18], [30.00, 31.32], [30.10, 31.32], [30.10, 31.18]],
            ],
            [
                'city_ar' => 'الجيزة', 'city_en' => 'Giza', 'center' => [30.0131, 31.2089],
                'zone_ar' => 'الجيزة', 'zone_en' => 'Giza',
                'polygon' => [[29.95, 31.10], [29.95, 31.17], [30.03, 31.17], [30.03, 31.10]],
            ],
        ];
    }
}
