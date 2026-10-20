<?php

namespace Tests\Feature\Provider;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\City;
use App\Models\VehicleCategory;
use App\Services\Provider\DocumentChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * Which documents are required for a provider / vehicle (DocumentRequirementResolver) and where each one stands.
 */
class DocumentRequirementsTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    /**
     * @return array<string, DocumentStatus|null> document type code => state of the provider's own checklist
     */
    private function states(\App\Models\ProviderProfile $provider): array
    {
        $items = app(DocumentChecklistService::class)->forProvider($provider->refresh());

        return collect($items)->mapWithKeys(fn ($item) => [$item->type->code => $item->state])->all();
    }

    public function test_an_unscoped_requirement_applies_to_everyone(): void
    {
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'id_card']);

        $this->assertSame(['id_card' => null], $this->states($provider));
    }

    public function test_country_and_city_scopes_must_match_the_providers_city(): void
    {
        $inCity = City::factory()->create();
        $elsewhere = City::factory()->create();
        $provider = $this->makeProvider(profile: ['city_id' => $inCity->id]);
        $this->documentType(['code' => 'country_doc'], ['country_id' => $inCity->country_id]);
        $this->documentType(['code' => 'other_country_doc'], ['country_id' => $elsewhere->country_id]);
        $this->documentType(['code' => 'city_doc'], ['city_id' => $inCity->id]);
        $this->documentType(['code' => 'other_city_doc'], ['city_id' => $elsewhere->id]);

        $this->assertEqualsCanonicalizing(['country_doc', 'city_doc'], array_keys($this->states($provider)));
    }

    public function test_provider_type_and_service_scopes(): void
    {
        $category = $this->bookableCategory();
        $other = $this->bookableCategory();
        $provider = $this->makeProvider(profile: ['provider_type' => ProviderType::IndividualDriver]);
        $this->documentType(['code' => 'company_papers'], ['provider_type' => ProviderType::CompanyFleet]);
        $this->documentType(['code' => 'driver_papers'], ['provider_type' => ProviderType::IndividualDriver]);
        $this->documentType(['code' => 'freight_permit'], ['service_category_id' => $category->id]);
        $this->documentType(['code' => 'moving_permit'], ['service_category_id' => $other->id]);

        $this->assertEqualsCanonicalizing(['driver_papers'], array_keys($this->states($provider)));

        $this->requestService($provider, $category, ServiceApprovalStatus::Requested);
        $this->assertEqualsCanonicalizing(['driver_papers', 'freight_permit'], array_keys($this->states($provider)));

        $this->requestService($provider, $other, ServiceApprovalStatus::Rejected);
        $this->assertNotContains('moving_permit', array_keys($this->states($provider)), 'a rejected service no longer scopes documents');
    }

    public function test_a_vehicle_category_scope_depends_on_the_vehicles_of_the_provider_or_the_vehicle(): void
    {
        $truck = VehicleCategory::factory()->create();
        $car = VehicleCategory::factory()->create();
        $provider = $this->makeProvider();
        $this->documentType(['code' => 'truck_licence'], ['vehicle_category_id' => $truck->id]);
        $this->vehicleDocumentType(['code' => 'truck_insurance'], ['vehicle_category_id' => $truck->id]);
        $this->vehicleDocumentType(['code' => 'car_insurance'], ['vehicle_category_id' => $car->id]);

        $this->assertSame([], $this->states($provider));

        $carVehicle = $this->vehicleFor($provider, VehicleStatus::Draft, ['vehicle_category_id' => $car->id]);
        $this->assertSame([], $this->states($provider));
        $this->assertSame(['car_insurance'], collect(app(DocumentChecklistService::class)->forVehicle($carVehicle->load('providerProfile')))->map(fn ($i) => $i->type->code)->all());

        $this->vehicleFor($provider, VehicleStatus::Draft, ['vehicle_category_id' => $truck->id]);
        $this->assertSame(['truck_licence' => null], $this->states($provider));
    }

    public function test_a_type_needed_by_several_requirements_is_mandatory_when_any_is_and_inactive_types_are_ignored(): void
    {
        $provider = $this->makeProvider();
        $type = $this->documentType(['code' => 'id_card'], ['provider_type' => ProviderType::IndividualDriver], mandatory: false);
        \App\Models\DocumentRequirement::factory()->create(['document_type_id' => $type->id, 'mandatory' => true]);
        $this->documentType(['code' => 'old_doc', 'active' => false]);

        $items = app(DocumentChecklistService::class)->forProvider($provider);

        $this->assertCount(1, $items);
        $this->assertTrue($items[0]->mandatory);
    }

    public function test_the_state_follows_the_best_document_and_the_expiry_date(): void
    {
        $provider = $this->makeProvider();
        $type = $this->documentType(['code' => 'licence', 'requires_expiry' => true]);

        $this->assertNull($this->states($provider)['licence']);

        $rejected = $this->providerDocument($provider, $type, DocumentStatus::Rejected);
        $this->assertSame(DocumentStatus::Rejected, $this->states($provider)['licence']);

        $this->providerDocument($provider, $type, DocumentStatus::Pending);
        $this->assertSame(DocumentStatus::Pending, $this->states($provider)['licence']);

        $approved = $this->providerDocument($provider, $type, DocumentStatus::Approved, now()->addDays(40)->toDateString());
        $this->assertSame(DocumentStatus::Approved, $this->states($provider)['licence'], 'a valid approved document beats a newer pending one');

        $approved->forceFill(['expires_at' => now()->subDay()->toDateString()])->save();
        $this->assertSame(DocumentStatus::Pending, $this->states($provider)['licence'], 'an approved document past its date no longer counts');

        $rejected->delete();
        $this->providerDocument($provider, $type, DocumentStatus::Approved, now()->subDays(2)->toDateString());
        \App\Models\ProviderDocument::query()->where('status', DocumentStatus::Pending)->delete();
        $this->assertSame(DocumentStatus::Expired, $this->states($provider)['licence'], 'approved but past its expiry date reads as expired even before the daily job');
    }

    public function test_a_document_expiring_today_is_still_valid(): void
    {
        $provider = $this->makeProvider();
        $type = $this->documentType(['code' => 'licence', 'requires_expiry' => true]);
        $this->providerDocument($provider, $type, DocumentStatus::Approved, now()->toDateString());

        $this->assertSame(DocumentStatus::Approved, $this->states($provider)['licence']);
    }

    public function test_unrequested_uploads_do_not_appear_on_the_checklist(): void
    {
        $provider = $this->makeProvider(ProviderStatus::DocumentsPending);
        $extra = $this->documentType(['code' => 'extra'], null);
        $this->providerDocument($provider, $extra);

        $this->assertSame([], $this->states($provider));
    }
}
