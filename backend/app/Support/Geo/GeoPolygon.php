<?php

namespace App\Support\Geo;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * A simple polygon (outer ring only) of at least three distinct vertices, stored closed. Holes are not
 * supported: service areas are drawn as one outline.
 *
 * @implements Arrayable<int, array{0: float, 1: float}>
 */
final readonly class GeoPolygon implements Arrayable, JsonSerializable
{
    public const MAX_VERTICES = 500;

    /**
     * @param list<GeoPoint> $ring closed: first and last vertex are equal
     */
    private function __construct(public array $ring) {}

    /**
     * @param list<GeoPoint> $vertices open or closed
     *
     * @throws InvalidCoordinatesException
     */
    public static function fromVertices(array $vertices): self
    {
        $count = count($vertices);

        if ($count > 1 && $vertices[0]->equals($vertices[$count - 1])) {
            array_pop($vertices);
            $count--;
        }

        $distinct = [];

        foreach ($vertices as $vertex) {
            $distinct[$vertex->key()] = true;
        }

        if ($count < 3 || count($distinct) < 3) {
            throw new InvalidCoordinatesException('A polygon needs at least three distinct vertices.');
        }

        if ($count > self::MAX_VERTICES) {
            throw new InvalidCoordinatesException('A polygon cannot have more than '.self::MAX_VERTICES.' vertices.');
        }

        return new self([...$vertices, $vertices[0]]);
    }

    /**
     * From `[[lat, lng], …]` (the shape the Leaflet polygon field submits), as a JSON string or an array.
     *
     * @throws InvalidCoordinatesException
     */
    public static function fromLatLngList(mixed $value): self
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            throw new InvalidCoordinatesException('The polygon is not a list of points.');
        }

        $vertices = [];

        foreach ($value as $pair) {
            if (! is_array($pair) || ! array_key_exists(0, $pair) || ! array_key_exists(1, $pair)) {
                throw new InvalidCoordinatesException('Each polygon point must be [lat, lng].');
            }

            $vertices[] = GeoPoint::make($pair[0], $pair[1]);
        }

        return self::fromVertices($vertices);
    }

    /**
     * @return list<GeoPoint> the vertices without the closing duplicate
     */
    public function vertices(): array
    {
        return array_slice($this->ring, 0, -1);
    }

    public function toWkt(): string
    {
        return 'POLYGON(('.implode(',', array_map(static fn (GeoPoint $p): string => sprintf('%.7F %.7F', $p->lng, $p->lat), $this->ring)).'))';
    }

    /**
     * Even-odd ray casting in the plane; only used for in-memory previews and tests. Persisted queries use ST_Contains.
     */
    public function containsPlanar(GeoPoint $point): bool
    {
        $inside = false;
        $vertices = $this->vertices();

        for ($i = 0, $j = count($vertices) - 1, $n = count($vertices); $i < $n; $j = $i++) {
            $a = $vertices[$i];
            $b = $vertices[$j];

            if ((($a->lat > $point->lat) !== ($b->lat > $point->lat))
                && ($point->lng < ($b->lng - $a->lng) * ($point->lat - $a->lat) / ($b->lat - $a->lat) + $a->lng)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    public function fingerprint(): string
    {
        return sha1($this->toWkt());
    }

    /**
     * Leaflet order: [[lat, lng], …] open ring.
     *
     * @return list<array{0: float, 1: float}>
     */
    public function toArray(): array
    {
        return array_map(static fn (GeoPoint $p): array => [round($p->lat, 7), round($p->lng, 7)], $this->vertices());
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
