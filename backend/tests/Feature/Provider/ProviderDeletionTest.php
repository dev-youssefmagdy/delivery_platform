<?php

namespace Tests\Feature\Provider;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\AccountDeletionRequest;
use App\Models\CustomerProfile;
use App\Models\ProviderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * ADR-77: deleting an account that is also a provider account. The request follows the provider profile; approving it
 * (a Providers-module action) anonymises everything, the Customers module keeps refusing these accounts.
 */
class ProviderDeletionTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePrivateDisk();
    }

    private function requestDeletion(\App\Models\ProviderProfile $provider): AccountDeletionRequest
    {
        $this->actingAsProvider($provider);
        $this->postJson('/api/v1/me/account-deletion-requests', ['reason' => 'I stopped driving'], $this->idempotencyHeaders())->assertCreated();

        return AccountDeletionRequest::query()->latest('id')->firstOrFail();
    }

    /**
     * An approved provider with a stored document, a vehicle with a photo and its own document.
     *
     * @return array{provider: \App\Models\ProviderProfile, paths: list<string>}
     */
    private function providerWithFiles(): array
    {
        ['provider' => $provider, 'vehicle' => $vehicle] = $this->eligibleProvider();
        $provider->user->forceFill(['phone_e164' => '+201556677889', 'email' => 'driver@example.test'])->save();
        $provider->forceFill(['date_of_birth' => '1990-01-02', 'is_online' => false])->save();
        $licence = $this->providerDocument($provider, $this->documentType(['code' => 'licence', 'requires_number' => true], null), DocumentStatus::Approved);
        $licence->forceFill(['number' => 'LIC-998877', 'path' => 'providers/'.$provider->uuid.'/documents/licence.jpg'])->save();
        $registration = $this->documentOfVehicle($vehicle, $this->vehicleDocumentType(['code' => 'registration'], null), DocumentStatus::Approved);
        $registration->forceFill(['path' => 'providers/'.$provider->uuid.'/documents/registration.jpg'])->save();
        $photo = $vehicle->photos()->create(['kind' => 'front', 'path' => 'providers/'.$provider->uuid.'/vehicles/front.jpg', 'mime' => 'image/jpeg', 'size_bytes' => 4, 'file_hash' => str_repeat('c', 64)]);
        $paths = [(string) $licence->path, (string) $registration->path, $photo->path];

        foreach ($paths as $path) {
            Storage::disk('private')->put($path, 'bytes');
        }

        return ['provider' => $provider->refresh(), 'paths' => $paths];
    }

    public function test_a_deletion_request_puts_the_provider_in_deletion_pending_and_cancelling_restores_it(): void
    {
        ['provider' => $provider] = $this->providerWithFiles();

        $request = $this->requestDeletion($provider);

        $this->assertSame(ProviderStatus::DeletionPending, $provider->refresh()->status);
        $this->assertSame(ProviderStatus::Approved, $request->previous_provider_status);
        $this->assertSame(UserStatus::DeletionPending, $provider->user->refresh()->status);
        $this->assertFalse($provider->is_online);

        $this->actingAsProvider($provider);
        $this->deleteJson('/api/v1/me/account-deletion-requests/'.$request->uuid)->assertOk();

        $this->assertSame(ProviderStatus::Approved, $provider->refresh()->status);
        $this->assertSame(UserStatus::Active, $provider->user->refresh()->status);
        $this->assertDatabaseHas('provider_status_events', ['provider_profile_id' => $provider->id, 'from_status' => 'deletion_pending', 'to_status' => 'approved']);
    }

    public function test_the_customers_module_still_refuses_an_account_that_is_also_a_provider(): void
    {
        ['provider' => $provider] = $this->providerWithFiles();
        CustomerProfile::query()->create(['user_id' => $provider->user_id]);
        $request = $this->requestDeletion($provider);
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);

        $this->postJson(route('admin.customers.deletion-requests.approve', [$provider->user, $request->uuid]), ['reason' => 'Processing it from customers'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'customer_is_provider');
        $this->assertSame(ProviderStatus::DeletionPending, $provider->refresh()->status);
    }

    public function test_approving_anonymises_the_account_and_deletes_every_document_file(): void
    {
        ['provider' => $provider, 'paths' => $paths] = $this->providerWithFiles();
        $request = $this->requestDeletion($provider);
        $user = $provider->user;
        $user->createToken('provider:device', ['provider']);
        $admin = $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::CustomersProcessDeletion]);
        $url = route('admin.providers.deletion-requests.approve', [$provider, $request->uuid]);

        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, ['reason' => 'Retention checked, nothing is owed'])->assertOk();

        $user->refresh();
        $this->assertTrue($user->isAnonymised());
        $this->assertNull($user->phone_e164);
        $this->assertNull($user->name);
        $this->assertNull($user->email);
        $this->assertSame(UserStatus::Deleted, $user->status);
        $this->assertSame(0, $user->tokens()->count());

        $provider->refresh();
        $this->assertNull($provider->date_of_birth);
        $this->assertNull($provider->city_id);
        $this->assertNull($provider->current_vehicle_id);
        $this->assertSame(ProviderStatus::DeletionPending, $provider->status, 'the profile keeps its history; the account status says deleted');

        foreach ($paths as $path) {
            Storage::disk('private')->assertMissing($path);
        }

        $this->assertSame([], ProviderDocument::query()->withTrashed()->whereNotNull('path')->pluck('path')->all());
        $this->assertNull(ProviderDocument::query()->withTrashed()->firstOrFail()->number);
        $this->assertSame(0, \App\Models\VehiclePhoto::query()->count());
        $vehicle = $provider->vehicles()->firstOrFail();
        $this->assertSame(VehicleStatus::Inactive, $vehicle->status);
        $this->assertNotEmpty($vehicle->plate_normalized, 'the plate stays for job and safety records');

        $request->refresh();
        $this->assertSame([DeletionRequestStatus::Completed, $admin->id, 'Retention checked, nothing is owed'], [$request->status, $request->processed_by_admin_id, $request->processing_note]);
        $log = \App\Models\AdminAuditLog::query()->where('action', AuditAction::Anonymised->value)->sole();
        $this->assertSame(['provider_profile', 'Retention checked, nothing is owed'], [$log->auditable_type, $log->reason]);
        $this->assertStringNotContainsString('LIC-998877', (string) json_encode($log->toArray()));

        $this->get(route('admin.providers.show', $provider))->assertOk()->assertSee(__('admin.providers.anonymised_name'));
        $this->postJson($url, ['reason' => 'Approving it a second time'])->assertForbidden();
    }

    public function test_rejecting_restores_the_provider_and_needs_a_reason(): void
    {
        ['provider' => $provider] = $this->providerWithFiles();
        $request = $this->requestDeletion($provider);
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::CustomersProcessDeletion]);
        $url = route('admin.providers.deletion-requests.reject', [$provider, $request->uuid]);

        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, ['reason' => 'The provider still has open matters'])->assertOk();

        $this->assertSame(ProviderStatus::Approved, $provider->refresh()->status);
        $this->assertSame(UserStatus::Active, $provider->user->refresh()->status);
        $this->assertSame(DeletionRequestStatus::Rejected, $request->refresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Rejected->value, 'auditable_type' => 'provider_profile', 'reason' => 'The provider still has open matters']);
    }

    public function test_processing_needs_the_legal_permission_and_provider_visibility(): void
    {
        ['provider' => $provider] = $this->providerWithFiles();
        $request = $this->requestDeletion($provider);

        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversSuspend, AdminPermission::DriversVerify]);
        $this->postJson(route('admin.providers.deletion-requests.approve', [$provider, $request->uuid]), ['reason' => 'No legal permission'])->assertForbidden();

        $this->actingAsAdmin([AdminPermission::CustomersProcessDeletion]);
        $this->postJson(route('admin.providers.deletion-requests.approve', [$provider, $request->uuid]), ['reason' => 'No provider access'])->assertForbidden();
        $this->assertSame(ProviderStatus::DeletionPending, $provider->refresh()->status);
    }

    public function test_the_tab_lists_the_request_with_actions_only_for_pending_ones(): void
    {
        ['provider' => $provider] = $this->providerWithFiles();
        $this->requestDeletion($provider);
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::CustomersProcessDeletion]);

        $json = $this->datatable(route('admin.providers.tabs.deletion-requests', $provider))->assertJsonPath('recordsTotal', 1)->json('data.0');
        $this->assertStringContainsString('data-confirm-action', $json['actions']);

        $this->actingAsAdmin([AdminPermission::DriversView]);
        $json = $this->datatable(route('admin.providers.tabs.deletion-requests', $provider))->json('data.0');
        $this->assertStringNotContainsString('data-confirm-action', $json['actions']);
    }

    public function test_a_customer_only_account_is_unaffected(): void
    {
        $customer = User::factory()->create();
        CustomerProfile::query()->create(['user_id' => $customer->id]);
        $this->actingAsProvider($customer, 'customer');

        $this->postJson('/api/v1/me/account-deletion-requests', ['reason' => 'Bye'], $this->idempotencyHeaders())->assertCreated();

        $this->assertSame(UserStatus::DeletionPending, $customer->refresh()->status);
        $this->assertNull(AccountDeletionRequest::query()->firstOrFail()->previous_provider_status);
    }
}
