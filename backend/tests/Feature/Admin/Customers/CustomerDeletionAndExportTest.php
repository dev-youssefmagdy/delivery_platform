<?php

namespace Tests\Feature\Admin\Customers;

use App\Enums\Admin\AdminExportStatus;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Models\AccountDeletionRequest;
use App\Models\AdminAuditLog;
use App\Models\AdminExport;
use App\Models\CustomerMessage;
use App\Models\PolicyVersion;
use App\Models\SavedPlace;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\UserDevice;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsCustomers;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Deletion requests (approve = anonymise, reject = restore) and exports (listing CSV, personal-data JSON).
 */
class CustomerDeletionAndExportTest extends TestCase
{
    use BuildsCustomers;
    use InteractsWithAdmin;
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: AccountDeletionRequest}
     */
    private function customerWithData(): array
    {
        Storage::fake('local');
        $customer = $this->customer(['name' => 'Mona Ali', 'phone_e164' => '+201001234567', 'email' => 'mona@example.test', 'photo_path' => 'photos/mona.jpg']);
        Storage::disk('local')->put('photos/mona.jpg', 'x');
        $customer->customerProfile()->update(['accessibility_needs' => 'Wheelchair', 'emergency_contact_name' => 'Sara', 'emergency_contact_phone' => '+201112223333', 'marketing_opt_in' => true]);
        UserDevice::query()->create(['user_id' => $customer->id, 'app' => ClientApp::Customer, 'platform' => 'android', 'device_id' => 'dev-1', 'push_token' => 'tok', 'push_token_hash' => str_repeat('a', 64)]);
        SavedPlace::factory()->create(['user_id' => $customer->id]);
        CustomerMessage::factory()->create(['user_id' => $customer->id]);
        $customer->createToken('customer:dev-1', ['customer']);
        UserConsent::query()->create(['user_id' => $customer->id, 'policy_version_id' => PolicyVersion::factory()->create()->id, 'locale' => 'ar', 'app' => ClientApp::Customer, 'ip' => '10.0.0.1', 'accepted_at' => now()]);

        return [$customer, $this->pendingDeletion($customer)];
    }

    public function test_approve_needs_permission_and_a_reason(): void
    {
        [$customer, $request] = $this->customerWithData();
        $url = route('admin.customers.deletion-requests.approve', [$customer, $request->uuid]);

        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $this->postJson($url, ['reason' => 'Verified the identity'])->assertForbidden();

        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);
        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->assertSame(UserStatus::DeletionPending, $customer->refresh()->status);
    }

    public function test_approve_anonymises_personal_data_and_keeps_references_and_legal_records(): void
    {
        [$customer, $request] = $this->customerWithData();
        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);

        $this->postJson(route('admin.customers.deletion-requests.approve', [$customer, $request->uuid]), ['reason' => 'Identity verified, no open obligations'])->assertOk();

        // The row stays (soft-deleted) so bookings/payments/ledger entries keep a valid reference to the same id.
        $kept = User::withTrashed()->findOrFail($customer->id);
        $this->assertTrue($kept->trashed());
        $this->assertSame($customer->uuid, $kept->uuid);
        $this->assertSame(UserStatus::Deleted, $kept->status);
        $this->assertNotNull($kept->anonymised_at);
        foreach (['name', 'phone_e164', 'email', 'photo_path', 'phone_verified_at'] as $attribute) {
            $this->assertNull($kept->{$attribute}, $attribute.' must be removed');
        }

        $profile = $kept->customerProfile;
        $this->assertNull($profile->emergency_contact_phone);
        $this->assertNull($profile->accessibility_needs);
        $this->assertFalse($profile->marketing_opt_in);
        $this->assertDatabaseMissing('user_devices', ['user_id' => $customer->id]);
        $this->assertDatabaseMissing('saved_places', ['user_id' => $customer->id]);
        $this->assertDatabaseMissing('customer_messages', ['user_id' => $customer->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $customer->id]);
        Storage::disk('local')->assertMissing('photos/mona.jpg');

        // Legal proof is retained: consents, the request itself and the audit trail.
        $this->assertDatabaseHas('user_consents', ['user_id' => $customer->id]);
        $request->refresh();
        $this->assertSame(DeletionRequestStatus::Completed, $request->status);
        $this->assertSame($admin->id, $request->processed_by_admin_id);
        $this->assertSame('Identity verified, no open obligations', $request->processing_note);
        $log = AdminAuditLog::query()->where('action', AuditAction::Anonymised->value)->sole();
        $this->assertSame((string) $customer->id, $log->auditable_id);
        $this->assertStringNotContainsString('Mona', json_encode([$log->before, $log->after, $log->meta, $log->reason]) ?: '');

        // Still visible in the panel, without personal data.
        $this->get(route('admin.customers.show', $customer))->assertOk()->assertDontSee('Mona Ali')->assertDontSee('+201001234567');
        $this->datatable(route('admin.customers.index'))->assertJsonPath('recordsTotal', 1);
        $this->postJson(route('admin.customers.suspend', $customer), ['reason' => 'cannot happen now'])->assertForbidden();
        $this->postJson(route('admin.customers.reveal', $customer))->assertForbidden();

        // Re-processing is refused.
        $this->postJson(route('admin.customers.deletion-requests.approve', [$customer, $request->uuid]), ['reason' => 'Second time'])->assertForbidden();
    }

    public function test_approve_is_blocked_while_the_customer_has_active_bookings(): void
    {
        $this->assertApprovalBlocked(bookings: 1, financialItems: 0, code: 'customer_has_active_bookings');
    }

    public function test_approve_is_blocked_while_the_customer_has_unsettled_money(): void
    {
        $this->assertApprovalBlocked(bookings: 0, financialItems: 2, code: 'customer_has_open_financial_items');
    }

    private function assertApprovalBlocked(int $bookings, int $financialItems, string $code): void
    {
        [$customer, $request] = $this->customerWithData();
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);

        $this->app->bind(CustomerActivityRepositoryInterface::class, fn (): CustomerActivityRepositoryInterface => new class($bookings, $financialItems) implements CustomerActivityRepositoryInterface
        {
            public function __construct(private readonly int $bookings, private readonly int $financialItems) {}

            public function activeBookingCount(User $user): int
            {
                return $this->bookings;
            }

            public function openFinancialItemCount(User $user): int
            {
                return $this->financialItems;
            }
        });

        $this->postJson(route('admin.customers.deletion-requests.approve', [$customer, $request->uuid]), ['reason' => 'Identity verified'])
            ->assertStatus(422)->assertJsonPath('errors._code', $code);

        $this->assertSame(UserStatus::DeletionPending, $customer->refresh()->status);
        $this->assertNotNull($customer->phone_e164);
        $this->assertSame(DeletionRequestStatus::Pending, $request->refresh()->status);
    }

    public function test_reject_restores_the_previous_status_and_keeps_the_data(): void
    {
        $customer = $this->customer([], UserStatus::Suspended);
        $request = $this->pendingDeletion($customer, UserStatus::Suspended);
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);
        $url = route('admin.customers.deletion-requests.reject', [$customer, $request->uuid]);

        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, ['reason' => 'Open dispute, keep the account'])->assertOk();

        $this->assertSame(UserStatus::Suspended, $customer->refresh()->status);
        $this->assertNotNull($customer->phone_e164);
        $request->refresh();
        $this->assertSame(DeletionRequestStatus::Rejected, $request->status);
        $this->assertSame('Open dispute, keep the account', $request->processing_note);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Rejected->value, 'auditable_id' => (string) $customer->id, 'reason' => 'Open dispute, keep the account']);
    }

    public function test_listing_export_needs_permission_is_audited_and_carries_masked_phones_only(): void
    {
        Storage::fake('local');
        $this->customer(['name' => 'Mona Ali', 'phone_e164' => '+201001234567']);

        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $this->postJson(route('admin.customers.export'))->assertForbidden();

        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersExport]);
        $statusUrl = $this->postJson(route('admin.customers.export'), ['filters' => ['search' => 'Mona']])->assertOk()->json('data.status_url');

        $export = AdminExport::query()->sole();
        $this->assertSame(AdminExportStatus::Completed, $export->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Exported->value, 'auditable_type' => 'admin_export', 'actor_id' => $admin->id]);

        $csv = $this->get($this->getJson($statusUrl)->json('data.download_url'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Mona Ali', $csv);
        $this->assertStringNotContainsString('+201001234567', $csv);
        $this->assertStringContainsString('4567', $csv);
    }

    public function test_personal_data_export_is_a_queued_audited_json_file(): void
    {
        [$customer] = $this->customerWithData();
        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $this->postJson(route('admin.customers.export-data', $customer))->assertForbidden();

        \App\Models\Booking::factory()->forCustomer($customer)->state(['pickup_address' => ['line' => 'Secret street']])->create();

        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersExport]);
        $statusUrl = $this->postJson(route('admin.customers.export-data', $customer))->assertOk()->json('data.status_url');

        $export = AdminExport::query()->sole();
        $this->assertSame(AdminExportStatus::Completed, $export->status);
        $this->assertSame('json', $export->format->value);
        $this->assertSame($customer->id, $export->subject_id);
        $log = AdminAuditLog::query()->where('action', AuditAction::Exported->value)->sole();
        $this->assertSame('user', $log->auditable_type);
        $this->assertSame((string) $customer->id, $log->auditable_id);
        $this->assertSame($admin->id, $log->actor_id);

        $download = $this->getJson($statusUrl)->assertJsonPath('data.finished', true)->json('data.download_url');
        $data = json_decode($this->get($download)->assertOk()->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('+201001234567', $data['account']['phone']);
        $this->assertSame('Mona Ali', $data['account']['name']);
        $this->assertSame('Wheelchair', $data['profile']['accessibility_needs']);
        $this->assertCount(1, $data['devices']);
        $this->assertArrayNotHasKey('push_token', $data['devices'][0]);
        $this->assertCount(1, $data['consents']);
        $this->assertCount(1, $data['saved_places']);
        $this->assertCount(1, $data['deletion_requests']);
        $this->assertNotContains('bookings', $data['sections_pending']);
        $this->assertContains('payments', $data['sections_pending']);
        $this->assertCount(1, $data['bookings']);
        $this->assertSame('Secret street', $data['bookings'][0]['pickup']['address']['line']);
        $this->assertSame(5000, $data['bookings'][0]['proposed_fare_minor']);

        // The export is owner-only.
        $this->actingAsAdmin([AdminPermission::CustomersExport]);
        $this->getJson($statusUrl)->assertForbidden();
    }
}
