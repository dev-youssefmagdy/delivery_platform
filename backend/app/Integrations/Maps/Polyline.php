<?php

namespace App\Integrations\Maps;

use App\Support\Geo\GeoPoint;

/**
 * Google encoded polyline algorithm (precision 5).
 */
final class Polyline
{
    /**
     * @param list<GeoPoint> $points
     */
    public static function encode(array $points): string
    {
        $out = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($points as $point) {
            $lat = (int) round($point->lat * 1e5);
            $lng = (int) round($point->lng * 1e5);
            $out .= self::value($lat - $prevLat).self::value($lng - $prevLng);
            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $out;
    }

    /**
     * @return list<array{0: float, 1: float}> [lat, lng] pairs
     */
    public static function decode(string $encoded): array
    {
        $points = [];
        $index = 0;
        $length = strlen($encoded);
        $lat = 0;
        $lng = 0;

        while ($index < $length) {
            foreach (['lat', 'lng'] as $axis) {
                $shift = 0;
                $result = 0;

                do {
                    $byte = ord($encoded[$index++]) - 63;
                    $result |= ($byte & 0x1F) << $shift;
                    $shift += 5;
                } while ($byte >= 0x20 && $index < $length);

                $delta = ($result & 1) ? ~($result >> 1) : ($result >> 1);
                $axis === 'lat' ? $lat += $delta : $lng += $delta;
            }

            $points[] = [$lat / 1e5, $lng / 1e5];
        }

        return $points;
    }

    private static function value(int $number): string
    {
        $number = $number < 0 ? ~($number << 1) : $number << 1;
        $out = '';

        while ($number >= 0x20) {
            $out .= chr((0x20 | ($number & 0x1F)) + 63);
            $number >>= 5;
        }

        return $out.chr($number + 63);
    }
}
