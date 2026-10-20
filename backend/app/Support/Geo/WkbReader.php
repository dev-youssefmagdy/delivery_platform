<?php

namespace App\Support\Geo;

use RuntimeException;

/**
 * Reads MySQL's internal geometry format (4-byte SRID + WKB, coordinates stored x = longitude, y = latitude
 * because every value is written with `axis-order=long-lat`).
 */
final class WkbReader
{
    public static function point(string $binary): GeoPoint
    {
        [$offset, $little] = self::header($binary, 1);
        $coords = self::doubles($binary, $offset, 2, $little);

        return GeoPoint::make($coords[1], $coords[0]);
    }

    public static function polygon(string $binary): GeoPolygon
    {
        [$offset, $little] = self::header($binary, 3);
        $rings = self::uint32($binary, $offset, $little);
        $offset += 4;

        if ($rings < 1) {
            throw new RuntimeException('Empty polygon.');
        }

        $count = self::uint32($binary, $offset, $little);
        $offset += 4;
        $vertices = [];

        for ($i = 0; $i < $count; $i++) {
            $coords = self::doubles($binary, $offset, 2, $little);
            $offset += 16;
            $vertices[] = GeoPoint::make($coords[1], $coords[0]);
        }

        return GeoPolygon::fromVertices($vertices);
    }

    /**
     * @return array{0: int, 1: bool} offset after the type field and whether the byte order is little endian
     */
    private static function header(string $binary, int $expectedType): array
    {
        if (strlen($binary) < 9 + 4) {
            throw new RuntimeException('Geometry value is too short.');
        }

        $little = ord($binary[4]) === 1;
        $type = self::uint32($binary, 5, $little);

        if ($type !== $expectedType) {
            throw new RuntimeException("Unexpected geometry type {$type}.");
        }

        return [9, $little];
    }

    private static function uint32(string $binary, int $offset, bool $little): int
    {
        $value = unpack($little ? 'V' : 'N', substr($binary, $offset, 4));

        return is_array($value) ? (int) $value[1] : throw new RuntimeException('Cannot read geometry.');
    }

    /**
     * @return list<float>
     */
    private static function doubles(string $binary, int $offset, int $count, bool $little): array
    {
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $chunk = substr($binary, $offset + $i * 8, 8);

            if (strlen($chunk) !== 8) {
                throw new RuntimeException('Cannot read geometry.');
            }

            $out[] = (float) unpack('e', $little ? $chunk : strrev($chunk))[1];
        }

        return $out;
    }
}
