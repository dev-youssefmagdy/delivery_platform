<?php

namespace App\Enums\Location;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How closely a driver's position is tracked right now (SRS §7.2, §10.3). The server decides the state and tells the
 * app, which picks its sampling frequency from it. `Off` means no tracking at all.
 */
enum TrackingState: string implements HasLabel
{
    use EnumHelpers;

    case Off = 'off';
    case Idle = 'idle';
    case ToPickup = 'to_pickup';
    case Active = 'active';

    public function isTracking(): bool
    {
        return $this !== self::Off;
    }

    /**
     * Seconds between samples asked of the app (config/location.php).
     */
    public function intervalSeconds(): int
    {
        return (int) config("location.intervals.{$this->value}", 0);
    }

    public function label(): string
    {
        return __("enums.tracking_state.{$this->value}");
    }
}
