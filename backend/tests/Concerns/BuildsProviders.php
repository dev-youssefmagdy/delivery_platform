<?php

namespace Tests\Concerns;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\DocumentSubject;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\City;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Fixtures for provider onboarding tests. Files go to a fake `private` disk; the file scanner is the noop driver
 * (phpunit.xml), so stored documents say `skipped`.
 */
trait BuildsProviders
{
    use BuildsGeography;

    protected function fakePrivateDisk(): void
    {
        Storage::fake('private');
    }

    /**
     * A provider with the given status (default: application started, documents pending).
     *
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $user
     */
    protected function makeProvider(ProviderStatus $status = ProviderStatus::DocumentsPending, array $profile = [], array $user = []): ProviderProfile
    {
        $owner = User::factory()->create($user);

        return ProviderProfile::factory()->status($status)->create($profile + ['user_id' => $owner->id]);
    }

    protected function actingAsProvider(ProviderProfile|User $provider, string $ability = 'provider'): User
    {
        $user = $provider instanceof ProviderProfile ? User::query()->findOrFail($provider->user_id) : $provider->refresh();
        Sanctum::actingAs($user, [$ability]);

        return $user;
    }

    /**
     * A document type, optionally required (mandatory) for everyone or for the given scope.
     *
     * @param array<string, mixed> $type
     * @param array<string, mixed>|null $scope requirement scope columns; null = no requirement row
     */
    protected function documentType(array $type = [], ?array $scope = [], bool $mandatory = true): DocumentType
    {
        $documentType = DocumentType::factory()->create($type);

        if ($scope !== null) {
            DocumentRequirement::factory()->create($scope + ['document_type_id' => $documentType->id, 'mandatory' => $mandatory]);
        }

        return $documentType;
    }

    protected function vehicleDocumentType(array $type = [], ?array $scope = [], bool $mandatory = true): DocumentType
    {
        return $this->documentType($type + ['applies_to' => DocumentSubject::Vehicle], $scope, $mandatory);
    }

    protected function vehicleFor(ProviderProfile $provider, VehicleStatus $status = VehicleStatus::Draft, array $attributes = []): Vehicle
    {
        return Vehicle::factory()->status($status)->create($attributes + ['provider_profile_id' => $provider->id]);
    }

    protected function providerDocument(ProviderProfile $provider, DocumentType $type, DocumentStatus $status = DocumentStatus::Pending, ?string $expires = null): ProviderDocument
    {
        return ProviderDocument::factory()->create([
            'provider_profile_id' => $provider->id,
            'document_type_id' => $type->id,
            'status' => $status,
            'expires_at' => $expires,
        ]);
    }

    protected function documentOfVehicle(Vehicle $vehicle, DocumentType $type, DocumentStatus $status = DocumentStatus::Pending, ?string $expires = null): VehicleDocument
    {
        return VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'document_type_id' => $type->id,
            'status' => $status,
            'expires_at' => $expires,
        ]);
    }

    protected function requestService(ProviderProfile $provider, ServiceCategory $category, ServiceApprovalStatus $status = ServiceApprovalStatus::Requested): ProviderServiceCategory
    {
        return ProviderServiceCategory::query()->create([
            'provider_profile_id' => $provider->id,
            'service_category_id' => $category->id,
            'status' => $status,
        ]);
    }

    /**
     * A fully approved provider: approved status, one approved service, one approved vehicle, no required documents.
     *
     * @return array{provider: ProviderProfile, category: ServiceCategory, vehicle: Vehicle}
     */
    protected function eligibleProvider(): array
    {
        $category = $this->bookableCategory();
        $provider = $this->makeProvider(ProviderStatus::Approved);
        $this->requestService($provider, $category, ServiceApprovalStatus::Approved);
        $vehicle = $this->vehicleFor($provider, VehicleStatus::Approved, ['vehicle_category_id' => $category->vehicleCategories()->firstOrFail()->id]);

        return ['provider' => $provider->refresh(), 'category' => $category, 'vehicle' => $vehicle];
    }

    protected function photo(string $name = 'photo.jpg', int $width = 640, int $height = 480): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    protected function pdf(string $name = 'doc.pdf', string $body = ''): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n{$body}\n%%EOF\n");
    }

    protected function providerCity(): City
    {
        return City::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    protected function applicationPayload(City $city, ?ServiceCategory $category = null, array $overrides = []): array
    {
        return $overrides + [
            'provider_type' => ProviderType::IndividualDriver->value,
            'city_id' => $city->id,
            'name' => 'Omar Hassan',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'service_category_ids' => $category === null ? [] : [$category->id],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function idempotencyHeaders(): array
    {
        return ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()];
    }
}
