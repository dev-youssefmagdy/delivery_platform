<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Operations\OperationsMapService;
use App\Support\Http\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Live operations map (bookings.view): the page and its JSON snapshot. The snapshot holds ids, coordinates, category codes
 * and statuses only; live updates arrive on `private-admin.operations`.
 */
final class OperationsMapController extends AdminController
{
    public function __construct(private readonly OperationsMapService $map) {}

    public function show(): View
    {
        return view('admin.operations.map', ['snapshot' => $this->map->snapshot()->toArray()]);
    }

    public function snapshot(): JsonResponse
    {
        return ApiResponse::success(data: $this->map->snapshot()->toArray());
    }
}
