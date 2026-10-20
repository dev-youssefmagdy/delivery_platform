<?php

namespace App\Services\Provider;

use App\DTOs\Provider\VerificationReview;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Models\Admin;
use App\Models\OwnedDocument;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use Illuminate\Support\Facades\Gate;

/**
 * Assembles the review screen: the provider, the required-document checklists, the document and photo list for the
 * viewer (each entry carries the endpoint that issues a fresh 5-minute signed URL; nothing is read from storage here)
 * and the requested services.
 */
final class VerificationReviewService
{
    public function __construct(
        private readonly ProviderService $providers,
        private readonly DocumentChecklistService $checklists,
        private readonly DocumentService $documents,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderServiceRepositoryInterface $services,
    ) {}

    public function build(ProviderProfile $profile, Admin $admin, bool $canRevealContact): VerificationReview
    {
        $profile->loadMissing(['user' => static fn ($query) => $query->withTrashed(), 'city', 'assignedAdmin']);
        $details = $this->providers->details($profile, $canRevealContact);
        $providerChecklist = $this->checklists->forProvider($profile);
        $vehicleChecklists = [];
        $viewer = [];

        foreach ($this->documents->listFor($profile) as $document) {
            $viewer[] = $this->documentItem($document, $admin);
        }

        foreach ($this->vehicles->forProvider($profile) as $vehicle) {
            $vehicle->setRelation('providerProfile', $profile);
            $vehicleChecklists[] = ['vehicle' => $vehicle, 'items' => $this->checklists->forVehicle($vehicle)];

            foreach ($vehicle->photos as $photo) {
                $viewer[] = [
                    'key' => 'photo-'.$photo->uuid,
                    'group' => $vehicle->plate_display,
                    'label' => __('admin.verification.photo_of', ['kind' => $photo->kind->label()]),
                    'kind' => 'photo',
                    'mime' => $photo->mime,
                    'status' => null,
                    'endpoint' => route('admin.vehicle-photos.url', $photo->uuid),
                ];
            }
        }

        return new VerificationReview(
            details: $details,
            profile: $profile,
            maskedPhone: $this->providers->maskedPhone($profile),
            providerChecklist: $providerChecklist,
            vehicleChecklists: $vehicleChecklists,
            viewerItems: $viewer,
            services: $this->services->forProvider($profile)->filter(static fn ($service): bool => $service->status !== ServiceApprovalStatus::Rejected || $profile->status->isAwaitingReview())->values()->all(),
            canApprove: Gate::forUser($admin)->allows('verify', $profile),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function documentItem(OwnedDocument $document, Admin $admin): array
    {
        $vehicle = $document instanceof VehicleDocument ? $document->vehicle : null;
        $route = $vehicle instanceof Vehicle ? 'admin.vehicle-documents.url' : 'admin.provider-documents.url';
        $number = $document->number;

        return [
            'key' => 'doc-'.$document->uuid,
            'id' => $document->uuid,
            'group' => $vehicle->plate_display ?? __('admin.verification.provider_group'),
            'label' => (string) $document->documentType->name,
            'kind' => 'document',
            'mime' => $document->mime,
            'status' => $document->status->value,
            'status_label' => $document->status->label(),
            'expires_at' => $document->expires_at?->toDateString(),
            'number_masked' => $number === null || $number === '' ? null : '••••'.mb_substr($number, -3),
            'scan' => $document->scan_status->value,
            'scan_label' => $document->scan_status->label(),
            'reason' => $document->rejection_reason,
            'endpoint' => route($route, $document->uuid),
            'can_approve' => Gate::forUser($admin)->allows('approve', $document),
            'can_reject' => Gate::forUser($admin)->allows('reject', $document),
            'approve_url' => route($vehicle instanceof Vehicle ? 'admin.vehicle-documents.approve' : 'admin.provider-documents.approve', $document->uuid),
            'reject_url' => route($vehicle instanceof Vehicle ? 'admin.vehicle-documents.reject' : 'admin.provider-documents.reject', $document->uuid),
        ];
    }
}
