<?php

namespace App\Services\Safety;

use App\Models\SafetyIncident;

/**
 * Result of raising a case: the case and an honest list of what was done, shown to the person as is.
 */
final readonly class ReportedIncident
{
    /**
     * @param list<array{code: string, status: string, message: string}> $actions
     */
    public function __construct(public SafetyIncident $incident, public array $actions, public bool $joinedExisting = false) {}
}
