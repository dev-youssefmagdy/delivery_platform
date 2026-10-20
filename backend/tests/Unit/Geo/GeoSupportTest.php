<?php

namespace Tests\Unit\Geo;

use App\Integrations\Maps\Polyline;
use App\Support\Geo\Distance;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\GeoPolygon;
use App\Support\Geo\InvalidCoordinatesException;
use App\Support\Geo\OperatingHours;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeoSupportTest extends TestCase
{
    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function impossible(): array
    {
        return [
            'lat too big' => [90.1, 10],
            'lat too small' => [-91, 10],
            'lng too big' => [10, 180.5],
            'null island' => [0, 0],
            'not numeric' => ['abc', 10],
            'null' => [null, 10],
            'nan' => [NAN, 10],
            'inf' => [10, INF],
        ];
    }

    #[DataProvider('impossible')]
    public function test_impossible_coordinates_are_rejected(mixed $lat, mixed $lng): void
    {
        $this->expectException(InvalidCoordinatesException::class);
        GeoPoint::make($lat, $lng);
    }

    public function test_valid_points_parse_and_format(): void
    {
        $point = GeoPoint::parse(' 30.0444 , 31.2357 ');
        $this->assertNotNull($point);
        $this->assertSame('POINT(31.2357000 30.0444000)', $point->toWkt());
        $this->assertNull(GeoPoint::parse('30.0444'));
        $this->assertNull(GeoPoint::parse('1,2,3'));
        $this->assertNull(GeoPoint::parse('0,0'));
        $this->assertSame(['lat' => 30.0444, 'lng' => 31.2357], $point->toArray());
    }

    public function test_haversine_distance(): void
    {
        $tahrir = GeoPoint::make(30.0444, 31.2357);
        $pyramids = GeoPoint::make(29.9792, 31.1342);

        $this->assertEqualsWithDelta(0.0, Distance::haversineMeters($tahrir, $tahrir), 0.001);
        $this->assertEqualsWithDelta(12_170, Distance::haversineMeters($tahrir, $pyramids), 100);
        $this->assertEqualsWithDelta(Distance::haversineMeters($tahrir, $pyramids), Distance::haversineMeters($pyramids, $tahrir), 0.001);
        $this->assertEqualsWithDelta(111_195, Distance::haversineMeters(GeoPoint::make(10, 20), GeoPoint::make(11, 20)), 100);
    }

    public function test_polygon_is_closed_and_validated(): void
    {
        $polygon = GeoPolygon::fromLatLngList([[30.0, 31.2], [30.0, 31.3], [30.1, 31.3], [30.1, 31.2]]);
        $this->assertCount(4, $polygon->vertices());
        $this->assertTrue($polygon->ring[0]->equals($polygon->ring[4]));
        $this->assertStringStartsWith('POLYGON((31.2000000 30.0000000,', $polygon->toWkt());

        $closed = GeoPolygon::fromLatLngList([[30.0, 31.2], [30.0, 31.3], [30.1, 31.3], [30.0, 31.2]]);
        $this->assertCount(3, $closed->vertices());

        $this->assertTrue($polygon->containsPlanar(GeoPoint::make(30.05, 31.25)));
        $this->assertFalse($polygon->containsPlanar(GeoPoint::make(30.2, 31.25)));
        $this->assertSame($polygon->toArray(), GeoPolygon::fromLatLngList(json_encode($polygon->toArray()))->toArray());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function badPolygons(): array
    {
        return [
            'two points' => [[[30.0, 31.2], [30.1, 31.3]]],
            'duplicates' => [[[30.0, 31.2], [30.0, 31.2], [30.0, 31.2]]],
            'not a list' => ['nope'],
            'bad pair' => [[[30.0], [30.1, 31.3], [30.0, 31.4]]],
            'impossible vertex' => [[[95.0, 31.2], [30.1, 31.3], [30.0, 31.4]]],
        ];
    }

    #[DataProvider('badPolygons')]
    public function test_bad_polygons_are_rejected(mixed $input): void
    {
        $this->expectException(InvalidCoordinatesException::class);
        GeoPolygon::fromLatLngList($input);
    }

    public function test_operating_hours(): void
    {
        $hours = new OperatingHours([['day' => 1, 'from' => '06:00', 'to' => '23:00'], ['day' => 5, 'from' => '12:00', 'to' => '23:30']]);
        $monday = CarbonImmutable::parse('2026-10-19 12:00', 'Africa/Cairo'); // Monday

        $this->assertTrue($hours->isOpenAt($monday));
        $this->assertTrue($hours->isOpenAt($monday->setTime(6, 0)));
        $this->assertFalse($hours->isOpenAt($monday->setTime(23, 0)));
        $this->assertFalse($hours->isOpenAt($monday->setTime(5, 59)));
        $this->assertFalse($hours->isOpenAt($monday->addDay()));
        $this->assertTrue($hours->isOpenAt($monday->addDays(4)->setTime(13, 0)));
        $this->assertTrue((new OperatingHours)->isOpenAt($monday));
        $this->assertTrue(OperatingHours::fromArray(null)->isAlwaysOpen());

        $next = $hours->nextOpeningAfter($monday->setTime(23, 30));
        $this->assertSame('2026-10-23 12:00', $next?->format('Y-m-d H:i'));
        $this->assertSame('2026-10-26 06:00', $hours->nextOpeningAfter($monday->addDays(4)->setTime(23, 59))?->format('Y-m-d H:i'));
        $this->assertNull((new OperatingHours)->nextOpeningAfter($monday));
    }

    public function test_polyline_round_trip_and_known_value(): void
    {
        // Example from Google's documentation.
        $points = [GeoPoint::make(38.5, -120.2), GeoPoint::make(40.7, -120.95), GeoPoint::make(43.252, -126.453)];
        $this->assertSame('_p~iF~ps|U_ulLnnqC_mqNvxq`@', Polyline::encode($points));

        $decoded = Polyline::decode('_p~iF~ps|U_ulLnnqC_mqNvxq`@');
        $this->assertCount(3, $decoded);
        $this->assertEqualsWithDelta(43.252, $decoded[2][0], 1e-5);
        $this->assertEqualsWithDelta(-126.453, $decoded[2][1], 1e-5);
    }
}
