<?php

namespace App\Services\Geography;

use App\DTOs\Geography\CoveragePreview;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Support\Geo\GeoPoint;

/**
 * Admin tool: resolve one point (or two, for a route) the same way the API does and show the result. A maps
 * failure is reported next to the resolution instead of failing the whole preview.
 */
final class CoveragePreviewService
{
    public function __construct(private readonly GeoService $geo, private readonly MapsService $maps) {}

    public function preview(GeoPoint $origin, ?GeoPoint $destination): CoveragePreview
    {
        $originArea = $this->geo->resolve($origin);

        if ($destination === null) {
            return new CoveragePreview($originArea);
        }

        $destinationArea = $this->geo->resolve($destination);

        try {
            return new CoveragePreview($originArea, $destinationArea, $this->maps->route($origin, $destination));
        } catch (ServiceUnavailableException|BusinessRuleViolation $e) {
            return new CoveragePreview($originArea, $destinationArea, routeError: $e->errorCode);
        }
    }
}
