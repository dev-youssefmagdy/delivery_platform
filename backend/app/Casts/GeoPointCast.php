<?php

namespace App\Casts;

use App\Support\Geo\GeometryExpression;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\WkbReader;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * POINT SRID 4326 column <-> GeoPoint. Writes a raw ST_GeomFromText expression (axis-order=long-lat).
 *
 * @implements CastsAttributes<GeoPoint, mixed>
 */
final class GeoPointCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?GeoPoint
    {
        if ($value instanceof GeometryExpression) {
            return $value->geometry instanceof GeoPoint ? $value->geometry : null;
        }

        return is_string($value) && $value !== '' ? WkbReader::point($value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?GeometryExpression
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof GeoPoint) {
            throw new InvalidArgumentException("[{$key}] must be a GeoPoint.");
        }

        return new GeometryExpression($value);
    }
}
