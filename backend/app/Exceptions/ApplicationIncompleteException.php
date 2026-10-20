<?php

namespace App\Exceptions;

use App\DTOs\Provider\OnboardingTaskItem;

/**
 * The provider application (or a vehicle) cannot be submitted yet (422 `application_incomplete` /
 * `vehicle_incomplete`). The pending tasks travel in `meta.tasks` so the app can send the driver to the right step.
 */
class ApplicationIncompleteException extends BusinessRuleViolation
{
    /**
     * @param list<OnboardingTaskItem> $tasks
     */
    public static function forTasks(string $code, array $tasks): static
    {
        return static::make($code, null, ['tasks' => array_map(static fn (OnboardingTaskItem $item): array => [
            'task' => $item->task->value,
            'reference' => $item->reference,
            'vehicle_id' => $item->vehicleUuid,
        ], $tasks)]);
    }

    public function meta(): array
    {
        return array_intersect_key($this->context, array_flip(['tasks']));
    }
}
