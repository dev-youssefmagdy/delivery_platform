<?php

namespace Tests\Feature\Provider;

use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\ProviderProfile;
use App\Models\ProviderStatusEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * POST /provider/applications, GET/PATCH /provider/application, POST /provider/application/submit.
 */
class ProviderApplicationApiTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePrivateDisk();
    }

    public function test_a_customer_token_cannot_use_the_provider_api(): void
    {
        $user = User::factory()->create();
        $this->actingAsProvider($user, 'customer');

        $this->getJson('/api/v1/provider/application')->assertForbidden();
        $this->postJson('/api/v1/provider/applications', [], $this->idempotencyHeaders())->assertForbidden();
    }

    public function test_a_provider_without_an_application_sees_started_false(): void
    {
        $this->actingAsProvider(User::factory()->create());

        $this->getJson('/api/v1/provider/application')->assertOk()->assertJsonPath('data.started', false);
    }

    public function test_starting_an_application_walks_the_state_machine_and_records_history(): void
    {
        $city = $this->providerCity();
        $category = $this->bookableCategory();
        $user = User::factory()->withoutProfile()->create();
        $this->actingAsProvider($user);

        $response = $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city, $category), $this->idempotencyHeaders())
            ->assertCreated()
            ->assertJsonPath('data.started', true)
            ->assertJsonPath('data.status', 'documents_pending')
            ->assertJsonPath('data.name', 'Omar Hassan')
            ->assertJsonPath('data.provider_type', 'individual_driver')
            ->assertJsonPath('data.services.0.service_category_id', $category->id)
            ->assertJsonPath('data.services.0.status', 'requested')
            ->assertJsonPath('data.steps.profile', true)
            ->assertJsonPath('data.steps.services', true)
            ->assertJsonPath('data.can_submit', false);

        $profile = ProviderProfile::query()->where('uuid', $response->json('data.id'))->firstOrFail();
        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame(
            [null, 'draft', 'phone_verified', 'profile_submitted'],
            ProviderStatusEvent::query()->orderBy('id')->get()->map(fn ($event) => $event->from_status?->value)->all(),
        );
        $this->assertSame(['draft', 'phone_verified', 'profile_submitted', 'documents_pending'], ProviderStatusEvent::query()->orderBy('id')->pluck('to_status')->map(fn ($s) => $s->value)->all());
        $this->assertSame('Omar Hassan', $user->refresh()->name);
    }

    public function test_starting_twice_conflicts_and_bad_input_is_rejected(): void
    {
        $city = $this->providerCity();
        $this->actingAsProvider(User::factory()->create());

        $this->postJson('/api/v1/provider/applications', ['provider_type' => 'nope'], $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonValidationErrors(['provider_type', 'city_id', 'name']);
        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city, null, ['date_of_birth' => now()->subYears(15)->toDateString()]), $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonPath('errors._code', 'provider_underage');
        $this->assertDatabaseCount('provider_profiles', 0);
        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city), $this->idempotencyHeaders())->assertCreated();
        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city), $this->idempotencyHeaders())
            ->assertStatus(409)->assertJsonPath('errors._code', 'application_exists');
    }

    public function test_an_inactive_city_and_unavailable_services_are_refused(): void
    {
        $inactive = \App\Models\City::factory()->inactive()->create();
        $this->actingAsProvider(User::factory()->create());

        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($inactive), $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonPath('errors._code', 'provider_city_unavailable');
        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($this->providerCity(), null, ['service_category_ids' => [999999]]), $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonPath('errors._code', 'provider_service_unavailable');
        $this->assertDatabaseCount('provider_profiles', 0);
    }

    public function test_the_request_requires_an_idempotency_key_and_replays(): void
    {
        $city = $this->providerCity();
        $this->actingAsProvider(User::factory()->create());
        $headers = $this->idempotencyHeaders();

        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city))->assertStatus(422)->assertJsonPath('errors._code', 'idempotency_key_required');
        $first = $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city), $headers)->assertCreated();
        $replay = $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city), $headers)->assertCreated();

        $this->assertSame($first->json('data.id'), $replay->json('data.id'));
        $this->assertSame(1, ProviderProfile::query()->count());
    }

    public function test_services_can_be_edited_and_a_removed_request_disappears(): void
    {
        $city = $this->providerCity();
        $one = $this->bookableCategory();
        $two = $this->bookableCategory();
        $this->actingAsProvider(User::factory()->create());
        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city, $one), $this->idempotencyHeaders())->assertCreated();

        $this->patchJson('/api/v1/provider/application', ['service_category_ids' => [$two->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.service_category_id', $two->id);

        $this->patchJson('/api/v1/provider/application', ['name' => 'Omar H', 'date_of_birth' => now()->toDateString()])->assertStatus(422);
        $this->patchJson('/api/v1/provider/application', ['name' => 'Omar H'])->assertOk()->assertJsonPath('data.name', 'Omar H');
    }

    public function test_the_application_cannot_be_edited_while_under_review(): void
    {
        $provider = $this->makeProvider(ProviderStatus::UnderReview);
        $this->actingAsProvider($provider);

        $this->patchJson('/api/v1/provider/application', ['name' => 'New Name'])->assertStatus(422)->assertJsonPath('errors._code', 'application_locked');
    }

    public function test_an_approved_provider_can_request_more_services_which_start_as_requested(): void
    {
        ['provider' => $provider, 'category' => $category] = $this->eligibleProvider();
        $extra = $this->bookableCategory();
        $this->actingAsProvider($provider);

        $this->patchJson('/api/v1/provider/application', ['service_category_ids' => [$category->id, $extra->id]])->assertOk();
        $this->patchJson('/api/v1/provider/application', ['name' => 'Someone Else'])->assertStatus(422)->assertJsonPath('errors._code', 'application_locked');

        $statuses = $provider->services()->pluck('status', 'service_category_id')->map(fn ($s) => $s->value)->all();
        $this->assertSame(['approved', 'requested'], [$statuses[$category->id], $statuses[$extra->id]]);
    }

    public function test_submission_lists_every_open_task_and_succeeds_when_complete(): void
    {
        $city = $this->providerCity();
        $category = $this->bookableCategory();
        $licence = $this->documentType(['code' => 'licence'], ['country_id' => null]);
        $registration = $this->vehicleDocumentType(['code' => 'registration']);
        $user = User::factory()->create();
        $this->actingAsProvider($user);
        $this->postJson('/api/v1/provider/applications', $this->applicationPayload($city, $category), $this->idempotencyHeaders())->assertCreated();

        $response = $this->postJson('/api/v1/provider/application/submit', [], $this->idempotencyHeaders())->assertStatus(422);
        $response->assertJsonPath('errors._code', 'application_incomplete');
        $tasks = array_column($response->json('meta.tasks'), 'task');
        $this->assertContains('add_vehicle', $tasks);
        $this->assertContains('upload_document', $tasks);

        // A vehicle with photos and its document, the provider's licence, then everything is in place.
        $vehicleResponse = $this->postJson('/api/v1/provider/vehicles', $this->vehiclePayload($category), $this->idempotencyHeaders())->assertCreated();
        $vehicleId = $vehicleResponse->json('data.id');
        foreach (['front', 'back'] as $kind) {
            $this->postJson("/api/v1/provider/vehicles/{$vehicleId}/photos", ['photo' => $this->photo(), 'kind' => $kind], $this->idempotencyHeaders())->assertCreated();
        }
        $this->postJson('/api/v1/provider/documents', ['document_type' => $registration->code, 'vehicle_id' => $vehicleId, 'file' => $this->photo('r.jpg')], $this->idempotencyHeaders())->assertCreated();
        $this->postJson("/api/v1/provider/vehicles/{$vehicleId}/submit-review", [], $this->idempotencyHeaders())->assertOk()->assertJsonPath('data.status', 'under_review');
        $this->postJson('/api/v1/provider/documents', ['document_type' => $licence->code, 'file' => $this->photo('l.jpg')], $this->idempotencyHeaders())->assertCreated();

        $this->getJson('/api/v1/provider/application')->assertOk()->assertJsonPath('data.can_submit', true)->assertJsonPath('data.resume_step', 'review');
        $this->postJson('/api/v1/provider/application/submit', [], $this->idempotencyHeaders())
            ->assertOk()->assertJsonPath('data.status', 'under_review')->assertJsonPath('data.editable', false);

        $profile = $user->providerProfile()->firstOrFail();
        $this->assertNotNull($profile->submitted_at);
        $this->assertSame(ProviderStatus::UnderReview, $profile->status);
    }

    public function test_a_provider_with_required_policies_must_accept_them_before_submitting(): void
    {
        $this->publishPolicyVersions();
        ['provider' => $provider, 'vehicle' => $vehicle] = $this->eligibleProvider();
        $provider->forceFill(['status' => ProviderStatus::DocumentsPending])->save();
        $vehicle->forceFill(['status' => VehicleStatus::UnderReview])->save();
        $this->actingAsProvider($provider);

        $response = $this->postJson('/api/v1/provider/application/submit', [], $this->idempotencyHeaders())->assertStatus(422);
        $this->assertContains('accept_policies', array_column($response->json('meta.tasks'), 'task'));
    }

    public function test_a_rejected_application_can_be_fixed_and_resubmitted(): void
    {
        ['provider' => $provider] = $this->eligibleProvider();
        $provider->forceFill(['status' => ProviderStatus::Rejected, 'status_reason' => 'Blurry licence'])->save();
        $this->actingAsProvider($provider);

        $this->getJson('/api/v1/provider/application')->assertOk()->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.status_reason', 'Blurry licence')->assertJsonPath('data.editable', true);
        $this->postJson('/api/v1/provider/application/submit', [], $this->idempotencyHeaders())->assertOk()->assertJsonPath('data.status', 'under_review')->assertJsonPath('data.status_reason', null);
        $this->assertDatabaseHas('provider_status_events', ['provider_profile_id' => $provider->id, 'from_status' => 'rejected', 'to_status' => 'under_review']);
    }

    public function test_the_cities_list_is_public_and_only_active(): void
    {
        $active = $this->providerCity();
        \App\Models\City::factory()->inactive()->create();

        $this->getJson('/api/v1/cities')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function vehiclePayload(\App\Models\ServiceCategory $category): array
    {
        return [
            'vehicle_category_id' => $category->vehicleCategories()->firstOrFail()->id,
            'make' => 'Toyota', 'model' => 'Corolla', 'year' => 2020, 'color' => 'White', 'plate' => 'ABC 1234',
            'passenger_capacity' => 4, 'capabilities' => ['air_conditioning'],
        ];
    }

    private function publishPolicyVersions(): void
    {
        foreach ([\App\Enums\Policy\PolicyType::Terms, \App\Enums\Policy\PolicyType::Privacy, \App\Enums\Policy\PolicyType::DriverTerms] as $type) {
            \App\Models\PolicyVersion::factory()->create(['type' => $type, 'version' => 1, 'status' => \App\Enums\Content\ContentStatus::Published, 'published_at' => now()]);
        }
    }
}
