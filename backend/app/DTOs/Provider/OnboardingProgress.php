<?php

namespace App\DTOs\Provider;

use App\Models\ProviderProfile;
use App\Models\Vehicle;

/**
 * What the driver app needs to render the wizard and the verification screen.
 */
final readonly class OnboardingProgress
{
    /**
     * @param array<string, bool> $steps step key => done (profile, services, vehicles, documents, declarations)
     * @param list<OnboardingTaskItem> $tasks everything still to do, in order
     * @param list<ChecklistItem> $providerChecklist
     * @param list<array{vehicle: Vehicle, items: list<ChecklistItem>}> $vehicleChecklists
     * @param list<ChecklistItem> $expiring approved documents expiring within the warning window
     * @param list<string> $pendingPolicyIds uuids of the policy versions still to accept
     * @param array{phone: string|null, email: string|null, whatsapp: string|null} $support
     */
    public function __construct(
        public ProviderProfile $profile,
        public array $steps,
        public string $resumeStep,
        public array $tasks,
        public array $providerChecklist,
        public array $vehicleChecklists,
        public array $expiring,
        public array $pendingPolicyIds,
        public array $support,
        public bool $canSubmit,
        public bool $editable,
    ) {}
}
