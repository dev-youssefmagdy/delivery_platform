<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\OnboardingTask;

/**
 * A pending task for the driver, with what it refers to (a document type code, a vehicle uuid).
 */
final readonly class OnboardingTaskItem
{
    public function __construct(
        public OnboardingTask $task,
        public ?string $reference = null,
        public ?string $vehicleUuid = null,
    ) {}
}
