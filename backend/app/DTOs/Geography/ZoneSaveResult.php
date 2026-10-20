<?php

namespace App\DTOs\Geography;

use App\Models\ServiceZone;
use Illuminate\Database\Eloquent\Collection;

/**
 * A saved zone plus the other zones its area overlaps (a warning for the admin, never an error).
 */
final readonly class ZoneSaveResult
{
    /**
     * @param Collection<int, ServiceZone> $overlaps
     */
    public function __construct(public ServiceZone $zone, public Collection $overlaps) {}
}
