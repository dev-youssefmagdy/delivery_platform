<?php

namespace App\Services\Provider;

use App\DTOs\Provider\ChecklistItem;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\DocumentSubject;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\OwnedDocument;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\DocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\DocumentRequirementRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Resolves WHICH documents a provider or vehicle needs (the `DocumentRequirementResolver` of the plan) and WHERE the
 * owner stands with each (the checklist). A requirement applies when every non-null scope of the row matches the
 * owner's context: country and city (of the provider's city), a requested/approved service category, a vehicle
 * category, the provider type. A document type needed by several rows is mandatory when any matching row is.
 */
final class DocumentChecklistService
{
    public function __construct(
        private readonly DocumentRequirementRepositoryInterface $requirements,
        private readonly ProviderDocumentRepositoryInterface $providerDocuments,
        private readonly VehicleDocumentRepositoryInterface $vehicleDocuments,
        private readonly ProviderServiceRepositoryInterface $services,
        private readonly VehicleRepositoryInterface $vehicles,
    ) {}

    /**
     * @return list<ChecklistItem>
     */
    public function forProvider(ProviderProfile $profile): array
    {
        $vehicleCategoryIds = $this->vehicles->forProvider($profile)
            ->filter(static fn (Vehicle $vehicle): bool => $vehicle->status !== VehicleStatus::Inactive)
            ->map(static fn (Vehicle $vehicle): int => $vehicle->vehicle_category_id)
            ->unique()->values()->all();

        $matching = $this->matching(DocumentSubject::Provider, $profile, $vehicleCategoryIds);

        return $this->items($matching, $this->providerDocuments, $profile->id, null);
    }

    /**
     * @return list<ChecklistItem>
     */
    public function forVehicle(Vehicle $vehicle): array
    {
        $profile = $vehicle->providerProfile;
        $matching = $this->matching(DocumentSubject::Vehicle, $profile, [$vehicle->vehicle_category_id]);

        return $this->items($matching, $this->vehicleDocuments, $vehicle->id, $vehicle);
    }

    /**
     * Mandatory items that are not satisfied (missing, pending, rejected or expired).
     *
     * @param list<ChecklistItem> $items
     * @return list<ChecklistItem>
     */
    public static function unsatisfiedMandatory(array $items): array
    {
        return array_values(array_filter($items, static fn (ChecklistItem $item): bool => $item->mandatory && ! $item->isSatisfied()));
    }

    /**
     * @param list<int> $vehicleCategoryIds
     * @return array<int, array{type: DocumentType, mandatory: bool}> keyed by document type id
     */
    private function matching(DocumentSubject $subject, ProviderProfile $profile, array $vehicleCategoryIds): array
    {
        $profile->loadMissing('city');
        $serviceIds = $this->services->categoryIds($profile, [ServiceApprovalStatus::Requested, ServiceApprovalStatus::Approved]);
        $countryId = $profile->city?->country_id;
        $matched = [];

        /** @var DocumentRequirement $requirement */
        foreach ($this->requirements->activeFor($subject) as $requirement) {
            if (! $this->applies($requirement, $countryId, $profile->city_id, $serviceIds, $vehicleCategoryIds, $profile->provider_type)) {
                continue;
            }

            $id = $requirement->document_type_id;
            $matched[$id] = [
                'type' => $requirement->documentType,
                'mandatory' => ($matched[$id]['mandatory'] ?? false) || $requirement->mandatory,
            ];
        }

        uasort($matched, static fn (array $a, array $b): int => [$a['type']->sort_order, $a['type']->id] <=> [$b['type']->sort_order, $b['type']->id]);

        return $matched;
    }

    /**
     * @param list<int> $serviceIds
     * @param list<int> $vehicleCategoryIds
     */
    private function applies(DocumentRequirement $requirement, ?int $countryId, ?int $cityId, array $serviceIds, array $vehicleCategoryIds, ?ProviderType $type): bool
    {
        return ($requirement->country_id === null || $requirement->country_id === $countryId)
            && ($requirement->city_id === null || $requirement->city_id === $cityId)
            && ($requirement->service_category_id === null || in_array($requirement->service_category_id, $serviceIds, true))
            && ($requirement->vehicle_category_id === null || in_array($requirement->vehicle_category_id, $vehicleCategoryIds, true))
            && ($requirement->provider_type === null || $requirement->provider_type === $type);
    }

    /**
     * @param array<int, array{type: DocumentType, mandatory: bool}> $matching
     * @return list<ChecklistItem>
     */
    private function items(array $matching, DocumentRepositoryInterface $documents, int $ownerId, ?Vehicle $vehicle): array
    {
        $owned = $documents->forOwner($ownerId)->groupBy('document_type_id');
        $today = CarbonImmutable::today();
        $items = [];

        foreach ($matching as $typeId => $entry) {
            /** @var \Illuminate\Support\Collection<int, OwnedDocument> $ofType */
            $ofType = $owned->get($typeId, collect());
            [$state, $document] = $this->summarise($ofType->all(), $today);
            $items[] = new ChecklistItem($entry['type'], $entry['mandatory'], $state, $document, $vehicle);
        }

        return $items;
    }

    /**
     * The document that decides the state of one type: a currently valid approved one, else the latest pending one,
     * else the latest reviewed one (rejected / expired). An approved document past its expiry date is `expired`.
     *
     * @param list<OwnedDocument> $documents newest first
     * @return array{0: DocumentStatus|null, 1: OwnedDocument|null}
     */
    public function summarise(array $documents, CarbonImmutable $today): array
    {
        $valid = array_values(array_filter($documents, static fn (OwnedDocument $document): bool => $document->isValidOn($today)));

        if ($valid !== []) {
            usort($valid, static fn (OwnedDocument $a, OwnedDocument $b): int => ($b->expires_at->timestamp ?? PHP_INT_MAX) <=> ($a->expires_at->timestamp ?? PHP_INT_MAX));

            return [DocumentStatus::Approved, $valid[0]];
        }

        foreach ($documents as $document) {
            if ($document->status === DocumentStatus::Pending) {
                return [DocumentStatus::Pending, $document];
            }
        }

        if ($documents === []) {
            return [null, null];
        }

        $latest = $documents[0];
        $state = $latest->status === DocumentStatus::Approved ? DocumentStatus::Expired : $latest->status;

        return [$state, $latest];
    }
}
