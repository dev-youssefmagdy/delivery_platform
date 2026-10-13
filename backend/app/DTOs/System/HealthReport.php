<?php

namespace App\DTOs\System;

use App\Enums\Common\HealthStatus;

/**
 * Result of the infrastructure health checks. `details` may contain internal information and is only for
 * authorised admin pages; the public endpoint exposes statuses only.
 */
final readonly class HealthReport
{
    /**
     * @param array<string, HealthStatus> $checks
     * @param array<string, array<string, mixed>> $details
     */
    public function __construct(
        public array $checks,
        public array $details = [],
    ) {}

    public function status(): HealthStatus
    {
        $statuses = array_values($this->checks);

        if (in_array(HealthStatus::Failing, $statuses, true)) {
            return HealthStatus::Failing;
        }

        if (in_array(HealthStatus::Degraded, $statuses, true)) {
            return HealthStatus::Degraded;
        }

        return HealthStatus::Ok;
    }

    public function isHealthy(): bool
    {
        return $this->status() !== HealthStatus::Failing;
    }

    /**
     * @return array{status: string, checks: array<string, string>}
     */
    public function toPublicArray(): array
    {
        return [
            'status' => $this->status()->value,
            'checks' => array_map(static fn (HealthStatus $status): string => $status->value, $this->checks),
        ];
    }
}
