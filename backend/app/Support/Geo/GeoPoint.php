<?php

namespace App\Support\Geo;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * An immutable WGS84 position. The factory rejects non-finite values, out-of-range values and the exact
 * (0, 0) "null island" pair, which is what a missing GPS fix usually looks like.
 *
 * @implements Arrayable<string, float>
 */
final readonly class GeoPoint implements Arrayable, JsonSerializable
{
    private function __construct(public float $lat, public float $lng) {}

    /**
     * @throws InvalidCoordinatesException
     */
    public static function make(float|int|string|null $lat, float|int|string|null $lng): self
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            throw new InvalidCoordinatesException('Coordinates must be numeric.');
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (! is_finite($lat) || ! is_finite($lng) || abs($lat) > 90.0 || abs($lng) > 180.0) {
            throw new InvalidCoordinatesException('Coordinates are out of range.');
        }

        if ($lat === 0.0 && $lng === 0.0) {
            throw new InvalidCoordinatesException('The (0, 0) position is not a valid location.');
        }

        return new self($lat, $lng);
    }

    public static function tryMake(float|int|string|null $lat, float|int|string|null $lng): ?self
    {
        try {
            return self::make($lat, $lng);
        } catch (InvalidCoordinatesException) {
            return null;
        }
    }

    /**
     * Parses "lat,lng" (query-string form).
     */
    public static function parse(string $value): ?self
    {
        $parts = explode(',', $value);

        return count($parts) === 2 ? self::tryMake(trim($parts[0]), trim($parts[1])) : null;
    }

    public function distanceTo(self $other): float
    {
        return Distance::haversineMeters($this, $other);
    }

    /**
     * WKT in x=lng y=lat order, to be used with `axis-order=long-lat`.
     */
    public function toWkt(): string
    {
        return sprintf('POINT(%.7F %.7F)', $this->lng, $this->lat);
    }

    public function key(): string
    {
        return sprintf('%.5F,%.5F', $this->lat, $this->lng);
    }

    public function equals(self $other): bool
    {
        return abs($this->lat - $other->lat) < 1e-9 && abs($this->lng - $other->lng) < 1e-9;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function toArray(): array
    {
        return ['lat' => round($this->lat, 7), 'lng' => round($this->lng, 7)];
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
