<?php

namespace Tests\Feature\Admin\Customers;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\UserStatus;
use App\Jobs\Customers\ReactivateExpiredSuspensions;
use App\Models\AdminAuditLog;
use App\Models\CustomerMessage;
use App\Services\Customers\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsCustomers;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Dangerous customer actions: suspend (optional expiry), restrict, reactivate, help message. Each needs a reason,
 * validates before submit (Precognition) and writes an audit entry with before/after.
 */
class CustomerAccountActionsTest extends TestCase
{
    use BuildsCustomers;
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_suspend_validates_with_precognition_and_requires_a_reason(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersSuspend]);
        $customer = $this->customer();
        $url = route('admin.customers.suspend', $customer);

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->precognitive('POST', $url, ['reason' => 'abuse', 'expires_at' => 'tomorrow'])->assertStatus(422)->assertJsonValidationErrors(['expires_at']);
        $this->precognitive('POST', $url, ['reason' => 'abuse', 'expires_at' => '2020-01-01 10:00'])->assertStatus(422)->assertJsonValidationErrors(['expires_at']);
        $this->precognitive('POST', $url, ['reason' => 'Repeated abuse of drivers'])->assertNoContent();

        $this->assertSame(UserStatus::Active, $customer->refresh()->status, 'precognition never persists');
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    public function test_suspend_records_status_reason_expiry_and_audit(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersSuspend]);
        $customer = $this->customer();
        $end = now()->addDays(3)->setTimezone((string) config('admin.display_timezone'))->format('Y-m-d H:i');

        $this->postJson(route('admin.customers.suspend', $customer), ['reason' => 'Repeated abuse of drivers', 'expires_at' => $end])->assertOk()->assertJsonPath('success', true);

        $customer->refresh();
        $this->assertSame(UserStatus::Suspended, $customer->status);
        $this->assertSame('Repeated abuse of drivers', $customer->status_reason);
        $this->assertTrue($customer->status_expires_at->between(now()->addDays(3)->subMinutes(2), now()->addDays(3)->addMinutes(2)));

        $log = AdminAuditLog::query()->where('action', AuditAction::Suspended->value)->sole();
        $this->assertSame('Repeated abuse of drivers', $log->reason);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('active', $log->before['status']);
        $this->assertSame('suspended', $log->after['status']);

        // A suspended customer cannot be suspended again (policy hides and refuses the action).
        $this->postJson(route('admin.customers.suspend', $customer), ['reason' => 'Again and again'])->assertForbidden();
    }

    public function test_restrict_and_reactivate_need_a_reason_and_are_audited(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersSuspend]);
        $customer = $this->customer();

        $this->postJson(route('admin.customers.restrict', $customer), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.customers.restrict', $customer), ['reason' => 'Chargebacks under review'])->assertOk();
        $this->assertSame(UserStatus::Restricted, $customer->refresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Restricted->value, 'auditable_id' => (string) $customer->id, 'reason' => 'Chargebacks under review']);

        $this->postJson(route('admin.customers.reactivate', $customer), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.customers.reactivate', $customer), ['reason' => 'Review finished, all clear'])->assertOk();
        $customer->refresh();
        $this->assertSame(UserStatus::Active, $customer->status);
        $this->assertNull($customer->status_reason);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Reactivated->value, 'auditable_id' => (string) $customer->id, 'reason' => 'Review finished, all clear']);

        $this->postJson(route('admin.customers.reactivate', $customer), ['reason' => 'Not suspended at all'])->assertForbidden();
    }

    public function test_suspend_permission_is_required(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersUpdate]);
        $customer = $this->customer();

        $this->postJson(route('admin.customers.suspend', $customer), ['reason' => 'abuse of the system'])->assertForbidden();
        $this->postJson(route('admin.customers.restrict', $customer), ['reason' => 'abuse of the system'])->assertForbidden();
        $this->assertSame(UserStatus::Active, $customer->refresh()->status);
    }

    public function test_help_message_is_stored_audited_and_listed(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersUpdate]);
        $customer = $this->customer();
        $url = route('admin.customers.messages.store', $customer);

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['subject', 'body', 'reason']);
        $this->precognitive('POST', $url, ['subject' => 'Verify your account', 'body' => 'Please confirm your phone number.', 'reason' => 'Account verification help'])->assertNoContent();
        $this->assertDatabaseCount('customer_messages', 0);

        $this->postJson($url, ['subject' => 'Verify your account', 'body' => 'Please confirm your phone number.', 'reason' => 'Account verification help'])->assertOk();

        $message = CustomerMessage::query()->sole();
        $this->assertSame($customer->id, $message->user_id);
        $this->assertSame($admin->id, $message->sent_by_admin_id);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::MessageSent->value, 'auditable_id' => (string) $customer->id, 'reason' => 'Account verification help']);

        $this->datatable(route('admin.customers.tabs.messages', $customer))->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.subject', 'Verify your account');
    }

    public function test_help_message_needs_the_update_permission(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);

        $this->postJson(route('admin.customers.messages.store', $this->customer()), ['subject' => 'Hello there', 'body' => 'Some text here', 'reason' => 'Help needed'])->assertForbidden();
    }

    public function test_scheduled_job_reactivates_only_expired_suspensions(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersSuspend]);
        $expired = $this->customer();
        $running = $this->customer();
        $open = $this->customer();
        $service = app(CustomerAccountService::class);
        $dto = static fn (?Carbon $end): \App\DTOs\Customers\SuspendCustomerData => new \App\DTOs\Customers\SuspendCustomerData('Cooling off period', $end?->toImmutable());

        $service->suspend($expired, $dto(now()->addMinutes(10)), $admin);
        $service->suspend($running, $dto(now()->addDays(2)), $admin);
        $service->suspend($open, $dto(null), $admin);

        $this->travel(11)->minutes();
        (new ReactivateExpiredSuspensions)->handle($service);

        $this->assertSame(UserStatus::Active, $expired->refresh()->status);
        $this->assertNull($expired->status_expires_at);
        $this->assertSame(UserStatus::Suspended, $running->refresh()->status);
        $this->assertSame(UserStatus::Suspended, $open->refresh()->status);

        $log = AdminAuditLog::query()->where('action', AuditAction::Reactivated->value)->where('auditable_id', (string) $expired->id)->sole();
        $this->assertSame('system', $log->actor_type);
        $this->assertNotEmpty($log->reason);

        // Idempotent: a second run changes nothing.
        $this->assertSame(0, $service->reactivateExpired());
    }

    public function test_job_does_not_override_a_manual_change_made_before_it_runs(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::CustomersSuspend]);
        $customer = $this->customer();
        $service = app(CustomerAccountService::class);
        $service->suspend($customer, new \App\DTOs\Customers\SuspendCustomerData('Short cooling off', now()->addMinutes(5)->toImmutable()), $admin);
        $service->reactivate($customer, 'Resolved by phone call', $admin);
        $service->restrict($customer, 'Keep an eye on it', $admin);

        $this->travel(10)->minutes();

        $this->assertSame(0, $service->reactivateExpired());
        $this->assertSame(UserStatus::Restricted, $customer->refresh()->status);
    }

    public function test_the_job_is_scheduled_every_minute(): void
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->first(static fn ($event): bool => $event->description === 'customers:reactivate-expired');

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }
}
