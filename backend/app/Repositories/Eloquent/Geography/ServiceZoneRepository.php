<?php

namespace App\Repositories\Eloquent\Geography;

use App\DTOs\Geography\ServiceZoneFilterData;
use App\Enums\Geo\ZoneStatus;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\GeoPolygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<ServiceZone>
 */
final class ServiceZoneRepository extends BaseRepository implements ServiceZoneRepositoryInterface
{
    private const GEOMETRY_FROM_WKT = "ST_GeomFromText(?, 4326, 'axis-order=long-lat')";

    /** Everything except the (large) polygon column. */
    private const LIGHT_COLUMNS = ['id', 'city_id', 'name', 'status', 'operating_hours', 'restricted_pickup', 'notes', 'is_demo', 'lock_version', 'created_at', 'updated_at'];

    public function __construct(ServiceZone $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ServiceZoneFilterData $filters): Builder
    {
        return $this->query()
            ->select(array_map(static fn (string $column): string => 'service_zones.'.$column, self::LIGHT_COLUMNS))
            ->with('city')
            ->withCount(['categoryLinks as enabled_categories_count' => static fn (Builder $query) => $query->where('active', true)])
            ->when($filters->search, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('city_id', $filters->cityIds))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (ZoneStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereHas('categoryLinks', static fn (Builder $links) => $links->where('active', true)->whereIn('service_category_id', $filters->categoryIds)))
            ->when($filters->restrictedPickup !== null, static fn (Builder $query) => $query->where('restricted_pickup', $filters->restrictedPickup));
    }

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->select(self::LIGHT_COLUMNS)
            ->when($term !== '', static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like)))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->select(self::LIGHT_COLUMNS)->whereKey($ids)->orderBy('id')->get();
    }

    public function containing(GeoPoint $point): Collection
    {
        return $this->query()
            ->with('city')
            ->where('status', ZoneStatus::Active->value)
            ->whereHas('city', static fn (Builder $query) => $query->where('active', true))
            ->whereRaw('ST_Contains(polygon, '.self::GEOMETRY_FROM_WKT.')', [$point->toWkt()])
            ->orderByRaw('ST_Area(polygon) asc')
            ->orderBy('id')
            ->get();
    }

    public function overlapping(GeoPolygon $polygon, ?int $exceptId = null): Collection
    {
        return $this->query()
            ->select(self::LIGHT_COLUMNS)
            ->with('city')
            ->whereRaw('ST_Intersects(polygon, '.self::GEOMETRY_FROM_WKT.') and not ST_Touches(polygon, '.self::GEOMETRY_FROM_WKT.')', [$polygon->toWkt(), $polygon->toWkt()])
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->orderBy('id')
            ->get();
    }

    public function isValidPolygon(GeoPolygon $polygon): bool
    {
        $row = DB::selectOne('select ST_IsValid('.self::GEOMETRY_FROM_WKT.') as valid', [$polygon->toWkt()]);

        return $row !== null && (int) $row->valid === 1;
    }

    public function areaSquareMeters(ServiceZone $zone): float
    {
        $row = DB::selectOne('select ST_Area(polygon) as area from service_zones where id = ?', [$zone->getKey()]);

        return $row === null ? 0.0 : (float) $row->area;
    }

    public function countForCity(int $cityId, ?string $status = null): int
    {
        return $this->query()->where('city_id', $cityId)->when($status !== null, static fn (Builder $query) => $query->where('status', $status))->count();
    }
}
