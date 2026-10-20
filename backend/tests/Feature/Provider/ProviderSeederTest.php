<?php

namespace Tests\Feature\Provider;

use App\Enums\Admin\AdminPermission;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\EligibilityReason;
use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderProfile;
use App\Services\Provider\ProviderEligibilityService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ProviderSeederTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_the_demo_providers_cover_every_scenario_and_are_idempotent(): void
    {
        Storage::fake('private');
        config(['geo.seed_demo' => true, 'provider.seed_demo' => true]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, ProviderProfile::query()->count());
        $byName = ProviderProfile::query()->with('user')->get()->keyBy(fn (ProviderProfile $p) => $p->user->name);
        $eligibility = app(ProviderEligibilityService::class);

        $approved = $byName['Demo approved driver (demo)'];
        $this->assertSame(ProviderStatus::Approved, $approved->status);
        $this->assertTrue($eligibility->check($approved)->isEligible(), 'the approved demo driver has valid documents and an approved vehicle');
        $this->assertSame(ProviderStatus::UnderReview, $byName['Demo pending driver (demo)']->status);
        $this->assertSame(ProviderStatus::Rejected, $byName['Demo rejected driver (demo)']->status);

        $expired = $byName['Demo expired licence driver (demo)'];
        $result = $eligibility->check($expired);
        $this->assertTrue($result->has(EligibilityReason::DocumentExpired));
        $this->assertSame(DocumentStatus::Approved, $expired->documents()->firstWhere('document_type_id', \App\Models\DocumentType::query()->where('code', 'driving_licence')->value('id'))->status, 'expired by date; the daily job marks it');
        $this->assertNotEmpty(Storage::disk('private')->allFiles());
    }

    /**
     * Strict mode forbids lazy loading, so rendering every admin page and tab over realistic data (documents,
     * vehicles, photos, history) proves each query eager loads what its view reads.
     */
    public function test_every_provider_page_and_tab_renders_over_the_demo_data(): void
    {
        Storage::fake('private');
        config(['geo.seed_demo' => true, 'provider.seed_demo' => true]);
        $this->seed(DatabaseSeeder::class);
        $this->actingAsAdmin(array_merge(AdminPermission::cases()));

        $this->get(route('admin.providers.index'))->assertOk();
        $this->datatable(route('admin.providers.index'))->assertOk()->assertJsonPath('recordsTotal', 4);
        $this->datatable(route('admin.verification.index'))->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->datatable(route('admin.vehicles.index'))->assertOk()->assertJsonPath('recordsTotal', 4);

        foreach (ProviderProfile::query()->get() as $provider) {
            $this->get(route('admin.providers.show', $provider))->assertOk();
            $this->get(route('admin.verification.review', $provider))->assertOk();

            foreach (['services', 'vehicles', 'documents', 'history', 'deletion-requests', 'audit'] as $tab) {
                $this->datatable(route('admin.providers.tabs.'.$tab, $provider))->assertOk();
            }

            foreach (\App\Models\Vehicle::query()->where('provider_profile_id', $provider->id)->get() as $vehicle) {
                $this->get(route('admin.vehicles.show', $vehicle))->assertOk();
                $this->datatable(route('admin.vehicles.tabs.documents', $vehicle))->assertOk()->assertJsonPath('recordsTotal', 2);
                $this->datatable(route('admin.vehicles.tabs.audit', $vehicle))->assertOk();
            }

            $this->actingAsProvider($provider);
            $this->getJson('/api/v1/provider/application')->assertOk();
            $this->getJson('/api/v1/provider/documents')->assertOk();
            $this->getJson('/api/v1/provider/vehicles')->assertOk();
            $this->getJson('/api/v1/provider/eligibility')->assertOk();
            $this->actingAsAdmin(array_merge(AdminPermission::cases()));
        }
    }

    public function test_nothing_is_seeded_when_demo_data_is_off(): void
    {
        config(['provider.seed_demo' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, ProviderProfile::query()->count());
        $this->assertGreaterThan(0, \App\Models\DocumentType::query()->count(), 'document types are reference data and always seeded');
    }
}
