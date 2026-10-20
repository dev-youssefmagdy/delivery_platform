<?php

namespace App\DTOs\Provider;

use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;

/**
 * Everything the side-by-side review screen shows.
 */
final readonly class VerificationReview
{
    /**
     * @param list<ChecklistItem> $providerChecklist
     * @param list<array{vehicle: \App\Models\Vehicle, items: list<ChecklistItem>}> $vehicleChecklists
     * @param list<array<string, mixed>> $viewerItems documents and photos for the viewer, with the endpoint that issues each signed URL
     * @param list<ProviderServiceCategory> $services
     */
    public function __construct(
        public ProviderDetails $details,
        public ProviderProfile $profile,
        public string $maskedPhone,
        public array $providerChecklist,
        public array $vehicleChecklists,
        public array $viewerItems,
        public array $services,
        public bool $canApprove,
    ) {}
}
