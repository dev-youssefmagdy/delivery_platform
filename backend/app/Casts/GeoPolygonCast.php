<?php

namespace App\Casts;

use App\Support\Geo\GeometryExpression;
use App\Support\Geo\GeoPolygon;
use App\Support\Geo\WkbReader;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * POLYGON SRID 4326 column <-> GeoPolygon.
 *
 * @implements CastsAttributes<GeoPolygon, mixed>
 */
final class GeoPolygonCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?GeoPolygon
    {
        if ($value instanceof GeometryExpression) {
            return $value->geometry instanceof GeoPolygon ? $value->geometry : null;
        }

        return is_string($value) && $value !== '' ? WkbReader::polygon($value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?GeometryExpression
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof GeoPolygon) {
            throw new InvalidArgumentException("[{$key}] must be a GeoPolygon.");
        }

        return new GeometryExpression($value);
    }
}
