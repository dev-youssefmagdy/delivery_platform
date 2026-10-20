<?php

namespace Tests\Feature\Provider;

use App\Enums\Provider\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * /provider/vehicles: create, edit, photos, submit for review, ownership.
 */
class ProviderVehicleApiTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePrivateDisk();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'vehicle_category_id' => \App\Models\VehicleCategory::factory()->create(['active' => true])->id,
            'make' => 'Toyota', 'model' => 'Corolla', 'year' => 2020, 'color' => 'White', 'plate' => 'أ ب ج ١٢٣٤',
            'passenger_capacity' => 4, 'capabilities' => ['air_conditioning', 'child_seat'],
        ];
    }

    public function test_a_vehicle_is_created_as_a_draft_with_a_normalised_unique_plate(): void
    {
        $provider = $this->makeProvider();
        $this->actingAsProvider($provider);

        $response = $this->postJson('/api/v1/provider/vehicles', $this->payload(), $this->idempotencyHeaders())->assertCreated();
        $response->assertJsonPath('data.status', 'draft')->assertJsonPath('data.plate', 'أ ب ج ١٢٣٤')->assertJsonPath('data.editable', true)
            ->assertJsonPath('data.capabilities', ['air_conditioning', 'child_seat']);

        $vehicle = Vehicle::query()->firstOrFail();
        $this->assertSame('أبج1234', $vehicle->plate_normalized);
        $this->assertSame($provider->id, $vehicle->provider_profile_id);

        // The same plate written differently (Latin digits, dashes) is the same plate, also for another provider.
        $this->postJson('/api/v1/provider/vehicles', $this->payload(['plate' => 'أبج-1234']), $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_plate_taken');
        $this->actingAsProvider($this->makeProvider());
        $this->postJson('/api/v1/provider/vehicles', $this->payload(['plate' => 'أ ب ج 1234']), $this->idempotencyHeaders())
            ->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_plate_taken');
    }

    public function test_input_is_validated(): void
    {
        $this->actingAsProvider($this->makeProvider());
        $post = fn (array $data) => $this->postJson('/api/v1/provider/vehicles', $data, $this->idempotencyHeaders());

        $post([])->assertStatus(422)->assertJsonValidationErrors(['vehicle_category_id', 'make', 'model', 'year', 'color', 'plate']);
        $post($this->payload(['capabilities' => ['flying']]))->assertStatus(422)->assertJsonValidationErrors(['capabilities.0']);
        $post($this->payload(['year' => 1970]))->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_year_invalid');
        $post($this->payload(['plate' => '!!!']))->assertStatus(422);
        $post($this->payload(['plate' => 'ABC']))->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_plate_invalid');
        $post($this->payload(['vehicle_category_id' => \App\Models\VehicleCategory::factory()->create(['active' => false])->id]))->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_category_unavailable');
    }

    public function test_a_provider_sees_only_its_own_vehicles_and_cannot_touch_others(): void
    {
        $mine = $this->vehicleFor($this->makeProvider());
        $theirs = $this->vehicleFor($this->makeProvider());
        $this->actingAsProvider($mine->providerProfile);

        $this->getJson('/api/v1/provider/vehicles')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->uuid);
        $this->getJson("/api/v1/provider/vehicles/{$theirs->uuid}")->assertForbidden();
        $this->patchJson("/api/v1/provider/vehicles/{$theirs->uuid}", ['color' => 'Red'])->assertForbidden();
        $this->deleteJson("/api/v1/provider/vehicles/{$theirs->uuid}")->assertForbidden();
        $this->postJson("/api/v1/provider/vehicles/{$theirs->uuid}/photos", ['photo' => $this->photo(), 'kind' => 'front'], $this->idempotencyHeaders())->assertForbidden();
        $this->postJson("/api/v1/provider/vehicles/{$theirs->uuid}/submit-review", [], $this->idempotencyHeaders())->assertForbidden();
        $this->getJson('/api/v1/provider/vehicles/'.fake()->uuid())->assertNotFound();
        $this->assertSame('Toyota', $theirs->refresh()->make);
    }

    public function test_a_draft_can_be_edited_and_deleted_but_not_after_review(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider());
        $this->actingAsProvider($vehicle->providerProfile);

        $this->patchJson("/api/v1/provider/vehicles/{$vehicle->uuid}", ['color' => 'Red', 'capabilities' => []])->assertOk()->assertJsonPath('data.color', 'Red');

        $vehicle->forceFill(['status' => VehicleStatus::UnderReview])->save();
        $this->patchJson("/api/v1/provider/vehicles/{$vehicle->uuid}", ['color' => 'Blue'])->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_not_editable');
        $this->deleteJson("/api/v1/provider/vehicles/{$vehicle->uuid}")->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_not_deletable');

        $draft = $this->vehicleFor($vehicle->providerProfile);
        $this->deleteJson("/api/v1/provider/vehicles/{$draft->uuid}")->assertOk();
        $this->assertModelMissing($draft);
    }

    public function test_photos_are_private_reencoded_and_limited(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider());
        $this->actingAsProvider($vehicle->providerProfile);
        config(['provider.vehicles.max_photos' => 2]);

        $response = $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/photos", ['photo' => $this->photo('p.jpg', 2000, 1000), 'kind' => 'front'], $this->idempotencyHeaders())->assertCreated();
        $photo = VehiclePhoto::query()->firstOrFail();

        $this->assertSame($photo->uuid, $response->json('data.photos.0.id'));
        $this->assertStringContainsString('signature=', $response->json('data.photos.0.url'));
        Storage::disk('private')->assertExists($photo->path);
        $this->assertSame(1600, getimagesizefromstring((string) Storage::disk('private')->get($photo->path))[0], 'photos are scaled to the configured edge');
        $this->getJson($response->json('data.photos.0.url'))->assertOk();

        $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/photos", ['photo' => $this->photo(), 'kind' => 'back'], $this->idempotencyHeaders())->assertCreated();
        $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/photos", ['photo' => $this->photo(), 'kind' => 'side'], $this->idempotencyHeaders())->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_photo_limit_reached');
        $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/photos", ['photo' => $this->pdf(), 'kind' => 'side'], $this->idempotencyHeaders())->assertStatus(422);

        $this->deleteJson("/api/v1/provider/vehicles/{$vehicle->uuid}/photos/{$photo->uuid}")->assertOk()->assertJsonCount(1, 'data.photos');
        Storage::disk('private')->assertMissing($photo->path);
    }

    public function test_submit_review_lists_missing_photos_and_documents_then_moves_to_under_review(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider());
        $registration = $this->vehicleDocumentType(['code' => 'registration']);
        $this->actingAsProvider($vehicle->providerProfile);

        $response = $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/submit-review", [], $this->idempotencyHeaders())->assertStatus(422);
        $response->assertJsonPath('errors._code', 'vehicle_incomplete');
        $this->assertEqualsCanonicalizing(['add_vehicle_photos', 'upload_document'], array_column($response->json('meta.tasks'), 'task'));
        $this->assertSame('registration', collect($response->json('meta.tasks'))->firstWhere('task', 'upload_document')['reference']);

        foreach (['front', 'back'] as $kind) {
            $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/photos", ['photo' => $this->photo(), 'kind' => $kind], $this->idempotencyHeaders())->assertCreated();
        }
        $this->postJson('/api/v1/provider/documents', ['document_type' => $registration->code, 'vehicle_id' => $vehicle->uuid, 'file' => $this->photo()], $this->idempotencyHeaders())->assertCreated();

        $this->postJson("/api/v1/provider/vehicles/{$vehicle->uuid}/submit-review", [], $this->idempotencyHeaders())
            ->assertOk()->assertJsonPath('data.status', 'under_review')->assertJsonPath('data.editable', false);
        $this->assertNotNull($vehicle->refresh()->submitted_at);
    }

    public function test_the_vehicle_limit_is_enforced(): void
    {
        $provider = $this->makeProvider();
        $this->actingAsProvider($provider);
        config(['provider.vehicles.max_per_provider' => 1]);
        $this->vehicleFor($provider);

        $this->postJson('/api/v1/provider/vehicles', $this->payload(), $this->idempotencyHeaders())->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_limit_reached');
    }
}
