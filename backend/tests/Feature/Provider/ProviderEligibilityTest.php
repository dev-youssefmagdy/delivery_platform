<?php

namespace Tests\Feature\Provider;

use App\Enums\Identity\UserStatus;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\EligibilityReason;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\VehicleCategory;
use App\Services\Provider\ProviderEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * Plan invariant I11: ProviderEligibilityService is the single computed answer, with typed reasons.
 */
class ProviderEligibilityTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    private function check(\App\Models\ProviderProfile $provider, ?\App\Models\ServiceCategory $category = null): \App\DTOs\Provider\EligibilityResult
    {
        return app(ProviderEligibilityService::class)->check($provider->refresh(), $category);
    }

    public function test_a_fully_approved_provider_is_eligible_and_exposes_its_usable_vehicles(): void
    {
        ['provider' => $provider, 'category' => $category, 'vehicle' => $vehicle] = $this->eligibleProvider();

        $result = $this->check($provider, $category);

        $this->assertTrue($result->isEligible());
        $this->assertSame([], $result->reasons());
        $this->assertSame([$vehicle->id], $result->usableVehicleIds);
    }

    public function test_an_application_that_is_not_approved_says_so(): void
    {
        foreach ([ProviderStatus::DocumentsPending, ProviderStatus::UnderReview, ProviderStatus::MoreInformationRequired, ProviderStatus::Rejected, ProviderStatus::DeletionPending] as $status) {
            $provider = $this->makeProvider($status);

            $this->assertTrue($this->check($provider)->has(EligibilityReason::NotApproved), $status->value);
        }
    }

    public function test_suspended_providers_and_blocked_accounts_are_not_eligible(): void
    {
        ['provider' => $provider] = $this->eligibleProvider();
        $provider->forceFill(['status' => ProviderStatus::TemporarilySuspended])->save();
        $this->assertTrue($this->check($provider)->has(EligibilityReason::ProviderSuspended));
        $this->assertFalse($this->check($provider)->has(EligibilityReason::NotApproved));

        $provider->forceFill(['status' => ProviderStatus::Approved])->save();
        $this->assertTrue($this->check($provider)->isEligible());

        foreach ([UserStatus::Suspended, UserStatus::Restricted, UserStatus::VerificationRequired, UserStatus::DeletionPending] as $status) {
            $provider->user->forceFill(['status' => $status])->save();
            $this->assertTrue($this->check($provider)->has(EligibilityReason::AccountBlocked), $status->value);
        }
    }

    public function test_mandatory_documents_produce_missing_pending_and_expired_reasons_but_optional_ones_do_not(): void
    {
        ['provider' => $provider] = $this->eligibleProvider();
        $licence = $this->documentType(['code' => 'licence', 'requires_expiry' => true]);
        $this->documentType(['code' => 'permit'], mandatory: false);

        $reasons = fn () => collect($this->check($provider)->issues)->map(fn ($i) => $i->reason->value.':'.$i->reference)->all();

        $this->assertSame(['document_missing:licence'], $reasons());

        $document = $this->providerDocument($provider, $licence, DocumentStatus::Pending);
        $this->assertSame(['document_not_approved:licence'], $reasons());

        $document->forceFill(['status' => DocumentStatus::Rejected])->save();
        $this->assertSame(['document_not_approved:licence'], $reasons());

        $document->forceFill(['status' => DocumentStatus::Approved, 'expires_at' => now()->subDay()->toDateString()])->save();
        $this->assertSame(['document_expired:licence'], $reasons());

        $document->forceFill(['expires_at' => now()->addMonth()->toDateString()])->save();
        $this->assertSame([], $reasons());
    }

    public function test_vehicle_reasons(): void
    {
        $provider = $this->makeProvider(ProviderStatus::Approved);
        $registration = $this->vehicleDocumentType(['code' => 'registration', 'requires_expiry' => true]);

        $this->assertTrue($this->check($provider)->has(EligibilityReason::NoApprovedVehicle));

        $draft = $this->vehicleFor($provider, VehicleStatus::UnderReview);
        $this->assertTrue($this->check($provider)->has(EligibilityReason::NoApprovedVehicle));

        $draft->forceFill(['status' => VehicleStatus::Approved])->save();
        $result = $this->check($provider);
        $this->assertFalse($result->has(EligibilityReason::NoApprovedVehicle));
        $this->assertTrue($result->has(EligibilityReason::DocumentMissing), 'an approved vehicle still needs its required documents');
        $this->assertSame($draft->uuid.':registration', $result->issues[0]->reference);
        $this->assertSame([], $result->usableVehicleIds);

        $this->documentOfVehicle($draft, $registration, DocumentStatus::Approved, now()->addYear()->toDateString());
        $this->assertSame([$draft->id], $this->check($provider)->usableVehicleIds);

        // A second, fully valid vehicle keeps the provider eligible even when the first one's document lapses.
        $other = $this->vehicleFor($provider, VehicleStatus::Approved);
        $this->documentOfVehicle($other, $registration, DocumentStatus::Approved, now()->addYear()->toDateString());
        \App\Models\VehicleDocument::query()->where('vehicle_id', $draft->id)->update(['expires_at' => now()->subDay()->toDateString()]);
        $result = $this->check($provider);
        $this->assertTrue($result->isEligible());
        $this->assertSame([$other->id], $result->usableVehicleIds);
    }

    public function test_service_reasons_when_a_category_is_requested(): void
    {
        ['provider' => $provider, 'category' => $category, 'vehicle' => $vehicle] = $this->eligibleProvider();
        $other = $this->bookableCategory();

        $result = $this->check($provider, $other);
        $this->assertTrue($result->has(EligibilityReason::ServiceNotApproved));

        $this->requestService($provider, $other, ServiceApprovalStatus::Requested);
        $this->assertTrue($this->check($provider, $other)->has(EligibilityReason::ServiceNotApproved));

        $provider->services()->where('service_category_id', $other->id)->update(['status' => ServiceApprovalStatus::Approved->value]);
        $result = $this->check($provider, $other);
        $this->assertTrue($result->has(EligibilityReason::VehicleCategoryMismatch), 'the only vehicle is of another category than the service accepts');
        $this->assertFalse($result->has(EligibilityReason::ServiceNotApproved));

        $vehicle->forceFill(['vehicle_category_id' => $other->vehicleCategories()->firstOrFail()->id])->save();
        $this->assertTrue($this->check($provider, $other)->isEligible());
        $this->assertTrue($this->check($provider, $category)->has(EligibilityReason::VehicleCategoryMismatch));
        $this->assertNotNull(VehicleCategory::query()->find($vehicle->vehicle_category_id));
    }

    public function test_the_eligibility_endpoint_returns_typed_reasons(): void
    {
        $provider = $this->makeProvider(ProviderStatus::UnderReview);
        ['provider' => $good] = $this->eligibleProvider();
        $this->documentType(['code' => 'licence'], ['provider_type' => $provider->provider_type]);
        $this->actingAsProvider($provider);

        $response = $this->getJson('/api/v1/provider/eligibility')->assertOk()->assertJsonPath('data.eligible', false);
        $reasons = array_column($response->json('data.reasons'), 'reason');
        $this->assertEqualsCanonicalizing(['not_approved', 'document_missing', 'no_approved_vehicle'], $reasons);

        $good->forceFill(['provider_type' => \App\Enums\Provider\ProviderType::Courier])->save();
        $this->actingAsProvider($good);
        $this->getJson('/api/v1/provider/eligibility')->assertOk()->assertJsonPath('data.eligible', true)->assertJsonPath('data.reasons', []);
    }
}
