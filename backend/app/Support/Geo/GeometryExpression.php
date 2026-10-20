<?php

namespace App\Support\Geo;

use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Grammar;

/**
 * Raw SQL for a spatial value that still remembers the PHP value it was built from, so a model keeps
 * returning it after save() without re-reading the row.
 */
final class GeometryExpression implements ExpressionContract
{
    public function __construct(public readonly GeoPoint|GeoPolygon $geometry) {}

    public function getValue(Grammar $grammar): string
    {
        return "ST_GeomFromText('".$this->geometry->toWkt()."', 4326, 'axis-order=long-lat')";
    }
}
