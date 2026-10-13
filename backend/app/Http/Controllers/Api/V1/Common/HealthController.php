<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Services\System\HealthCheckService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public liveness/readiness probe. Statuses only; details are reserved for the admin system page.
 */
final class HealthController extends Controller
{
    public function __invoke(HealthCheckService $health): JsonResponse
    {
        $report = $health->check();

        return $report->isHealthy()
            ? ApiResponse::success(__('api.health.ok'), $report->toPublicArray())
            : ApiResponse::error(__('api.health.failing'), 503, 'service_unavailable', meta: $report->toPublicArray());
    }
}
