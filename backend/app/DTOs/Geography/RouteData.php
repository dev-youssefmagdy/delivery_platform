<?php

namespace App\DTOs\Geography;

/**
 * A driving route as the maps provider computed it. `polyline` is Google-style encoded (precision 5).
 */
final readonly class RouteData
{
    public function __construct(
        public int $distanceMeters,
        public int $durationSeconds,
        public string $polyline,
        public string $provider,
    ) {}

    /**
     * @return array{distance_m: int, duration_s: int, polyline: string, provider: string}
     */
    public function toArray(): array
    {
        return [
            'distance_m' => $this->distanceMeters,
            'duration_s' => $this->durationSeconds,
            'polyline' => $this->polyline,
            'provider' => $this->provider,
        ];
    }
}
