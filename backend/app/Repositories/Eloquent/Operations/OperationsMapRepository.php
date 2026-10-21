<?php

namespace App\Repositories\Eloquent\Operations;

use App\Enums\Booking\BookingStatus;
use App\Repositories\Contracts\Operations\OperationsMapRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class OperationsMapRepository implements OperationsMapRepositoryInterface
{
    public function onlineProviders(CarbonInterface $freshSince, int $limit): array
    {
        $busy = BookingStatus::assigned();

        return DB::table('provider_last_locations as location')
            ->join('provider_profiles as profile', 'profile.id', '=', 'location.provider_profile_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'profile.current_vehicle_id')
            ->leftJoin('vehicle_categories as category', 'category.id', '=', 'vehicles.vehicle_category_id')
            ->where('profile.is_online', true)
            ->where('location.recorded_at', '>=', $freshSince)
            ->orderByDesc('location.recorded_at')
            ->limit($limit)
            ->selectRaw('profile.uuid as uuid, ST_Latitude(location.point) as lat, ST_Longitude(location.point) as lng, category.code as category, location.recorded_at as recorded_at')
            ->selectRaw('exists (select 1 from bookings b where b.provider_id = profile.id and b.status in ('.implode(',', array_fill(0, count($busy), '?')).')) as busy', array_map(static fn (BookingStatus $status): string => $status->value, $busy))
            ->get()
            ->map(static fn (object $row): array => [
                'uuid' => (string) $row->uuid,
                'lat' => round((float) $row->lat, 5),
                'lng' => round((float) $row->lng, 5),
                'category' => $row->category === null ? null : (string) $row->category,
                'busy' => (bool) $row->busy,
                'recorded_at' => (string) $row->recorded_at,
            ])
            ->all();
    }

    public function activeBookings(int $limit): array
    {
        $statuses = [...BookingStatus::searchingValues(), ...array_map(static fn (BookingStatus $status): string => $status->value, BookingStatus::assigned())];

        return DB::table('bookings')
            ->join('service_categories as category', 'category.id', '=', 'bookings.service_category_id')
            ->whereIn('bookings.status', $statuses)
            ->orderByDesc('bookings.id')
            ->limit($limit)
            ->selectRaw('bookings.uuid as uuid, bookings.code as code, ST_Latitude(bookings.pickup_point) as lat, ST_Longitude(bookings.pickup_point) as lng, category.code as category, bookings.status as status')
            ->get()
            ->map(static fn (object $row): array => [
                'uuid' => (string) $row->uuid,
                'code' => (string) $row->code,
                'lat' => round((float) $row->lat, 5),
                'lng' => round((float) $row->lng, 5),
                'category' => (string) $row->category,
                'status' => (string) $row->status,
            ])
            ->all();
    }

    public function counts(CarbonInterface $freshSince): array
    {
        $statuses = [...BookingStatus::searchingValues(), ...array_map(static fn (BookingStatus $status): string => $status->value, BookingStatus::assigned())];

        return [
            'providers' => DB::table('provider_last_locations as location')
                ->join('provider_profiles as profile', 'profile.id', '=', 'location.provider_profile_id')
                ->where('profile.is_online', true)
                ->where('location.recorded_at', '>=', $freshSince)
                ->count(),
            'bookings' => DB::table('bookings')->whereIn('status', $statuses)->count(),
        ];
    }
}
