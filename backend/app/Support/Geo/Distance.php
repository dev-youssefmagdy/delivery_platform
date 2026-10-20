<?php

namespace App\Support\Geo;

/**
 * Great-circle helpers. Haversine is for ranking and sanity checks; zone membership is decided by MySQL
 * spatial functions (ST_Contains) so both sides never disagree about borders.
 */
final class Distance
{
    public const EARTH_RADIUS_METERS = 6371008.8;

    public static function haversineMeters(GeoPoint $a, GeoPoint $b): float
    {
        $lat1 = deg2rad($a->lat);
        $lat2 = deg2rad($b->lat);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad($b->lng - $a->lng);

        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(min(1.0, sqrt($h)));
    }

    /**
     * Total length of a path.
     *
     * @param list<GeoPoint> $points
     */
    public static function pathMeters(array $points): float
    {
        $total = 0.0;

        for ($i = 1, $count = count($points); $i < $count; $i++) {
            $total += self::haversineMeters($points[$i - 1], $points[$i]);
        }

        return $total;
    }
}
