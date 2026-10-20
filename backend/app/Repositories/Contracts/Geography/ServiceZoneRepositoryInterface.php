<?php

namespace App\Repositories\Contracts\Geography;

use App\DTOs\Geography\ServiceZoneFilterData;
use App\Models\ServiceZone;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\GeoPolygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ServiceZone>
 */
interface ServiceZoneRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Listing query (no polygon column: it is only needed on the detail pages).
     *
     * @return Builder<ServiceZone>
     */
    public function datatableQuery(ServiceZoneFilterData $filters): Builder;

    /**
     * @return Collection<int, ServiceZone>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, ServiceZone>
     */
    public function findManyByIds(array $ids): Collection;

    /**
     * Active zones of active cities that contain the point (ST_Contains), most specific (smallest) first.
     *
     * @return Collection<int, ServiceZone>
     */
    public function containing(GeoPoint $point): Collection;

    /**
     * Zones whose area overlaps the polygon (touching borders do not count), optionally ignoring one zone.
     *
     * @return Collection<int, ServiceZone>
     */
    public function overlapping(GeoPolygon $polygon, ?int $exceptId = null): Collection;

    /**
     * MySQL's verdict on the geometry (closed ring, no self-intersection).
     */
    public function isValidPolygon(GeoPolygon $polygon): bool;

    public function areaSquareMeters(ServiceZone $zone): float;

    /**
     * Number of zones in `status` for the city.
     */
    public function countForCity(int $cityId, ?string $status = null): int;
}
