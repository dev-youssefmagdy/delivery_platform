<?php

namespace Tests\Feature\Admin\Customers;

use App\DataTables\Admin\Customers\CustomersDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\UserStatus;
use App\Models\City;
use App\Models\CustomerProfile;
use App\Models\SavedPlace;
use App\Models\ServiceZone;
use App\Models\User;
use App\Support\Geo\GeoPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCustomers;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Customers: access, listing filters, show page, PII masking and reveal.
 */
class CustomerAdminTest extends TestCase
{
    use BuildsCustomers;
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.customers.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->get(route('admin.customers.index'))->assertForbidden();
        $this->datatable(route('admin.customers.index'))->assertForbidden();
        $this->getJson(route('admin.customers.show', $customer))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.customers.tabs.devices', $customer))->assertForbidden();
        $this->postJson(route('admin.customers.suspend', $customer), ['reason' => 'because reasons'])->assertForbidden();
        $this->postJson(route('admin.customers.reveal', $customer))->assertForbidden();
        $this->postJson(route('admin.customers.export'))->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card_and_the_booking_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);

        $this->get(route('admin.customers.index'))->assertOk()
            ->assertSee('data-filters-for="'.CustomersDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.CustomersDataTable::TABLE_ID.'"', false)
            ->assertSee('name="filters[has_active_booking]"', false)
            ->assertDontSee('P2.3');
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $match = $this->customer(['name' => 'Needle Customer', 'phone_e164' => '+201001112222', 'created_at' => '2026-03-10 10:00:00']);
        $other = $this->customer(['name' => 'Someone Else', 'phone_e164' => '+201009998888', 'created_at' => '2026-06-10 10:00:00'], UserStatus::Suspended);
        User::factory()->create(['name' => 'Provider Only']); // no customer profile: not a customer
        $url = route('admin.customers.index');

        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable($url, ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['search' => $other->uuid])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
        $this->datatable($url, ['search' => '+201001112222'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['search' => '2222'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['search' => '١٢٣'])->assertJsonPath('recordsFiltered', 0);
        $this->datatable($url, ['statuses' => ['suspended']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
        $this->datatable($url, ['created_at' => ['from' => '2026-06-01', 'to' => '2026-06-30']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);

        $this->pendingDeletion($match);
        $this->datatable($url, ['deletion_requested' => '1'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['deletion_requested' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
    }

    public function test_city_filter_matches_customers_with_a_saved_place_inside_a_zone_of_the_city(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $zone = ServiceZone::factory()->create(); // square 30.0..30.1 N, 31.2..31.3 E
        $inside = $this->customer();
        $outside = $this->customer();
        SavedPlace::factory()->create(['user_id' => $inside->id, 'point' => GeoPoint::make(30.05, 31.25)]);
        SavedPlace::factory()->create(['user_id' => $outside->id, 'point' => GeoPoint::make(31.5, 29.9)]);

        $this->datatable(route('admin.customers.index'), ['city_ids' => [$zone->city_id]])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $inside->id);
        $this->datatable(route('admin.customers.index'), ['city_ids' => [City::factory()->create()->id]])->assertJsonPath('recordsFiltered', 0);
    }

    public function test_listing_never_contains_a_full_phone_number(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersViewPii]);
        $this->customer(['phone_e164' => '+201001234567']);

        $response = $this->datatable(route('admin.customers.index'))->assertOk();

        $this->assertStringNotContainsString('+201001234567', $response->getContent());
        $this->assertStringContainsString('+20', $response->getContent());
        $this->assertStringContainsString('4567', $response->getContent());
    }

    public function test_show_page_masks_contact_details_and_marks_modules_that_are_not_built_yet(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $customer = $this->customer(['name' => 'Mona Ali', 'phone_e164' => '+201001234567', 'email' => 'mona@example.test']);
        CustomerProfile::query()->where('user_id', $customer->id)->update(['emergency_contact_name' => 'Sara', 'emergency_contact_phone' => '+201112223333']);

        $response = $this->get(route('admin.customers.show', $customer))->assertOk()->assertSee('Mona Ali');

        $response->assertDontSee('+201001234567')->assertDontSee('mona@example.test')->assertDontSee('+201112223333');
        $response->assertSee('P3.2')->assertSee('P3.3')->assertSee('P3.7')->assertSee('P2.5');
        $response->assertSee(route('admin.customers.tabs.bookings', $customer), false);
        $response->assertDontSee('adminReveal(', false);
        $response->assertDontSee(route('admin.customers.tabs.saved-places', $customer), false);
        $response->assertSee(route('admin.customers.tabs.devices', $customer), false);
        $this->assertDatabaseMissing('admin_audit_logs', ['action' => AuditAction::PhoneRevealed->value]);
    }

    public function test_reveal_needs_view_pii_and_is_audited_without_storing_the_values(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersViewPii]);
        $customer = $this->customer(['phone_e164' => '+201001234567', 'email' => 'mona@example.test']);

        $this->get(route('admin.customers.show', $customer))->assertOk()->assertSee('adminReveal(', false);
        $this->postJson(route('admin.customers.reveal', $customer))->assertOk()
            ->assertJsonPath('data.phone', '+201001234567')
            ->assertJsonPath('data.email', 'mona@example.test');

        $log = \App\Models\AdminAuditLog::query()->where('action', AuditAction::PhoneRevealed->value)->sole();
        $this->assertSame((string) $customer->id, $log->auditable_id);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertStringNotContainsString('+201001234567', json_encode([$log->before, $log->after, $log->meta], JSON_UNESCAPED_UNICODE) ?: '');
    }

    public function test_saved_places_tab_needs_view_pii(): void
    {
        $customer = $this->customer();
        SavedPlace::factory()->create(['user_id' => $customer->id, 'label' => 'Secret Home']);

        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $this->datatable(route('admin.customers.tabs.saved-places', $customer))->assertForbidden();

        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersViewPii]);
        $this->datatable(route('admin.customers.tabs.saved-places', $customer))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.label', 'Secret Home');
    }

    public function test_lazy_tabs_each_serve_their_own_datatable(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersViewPii]);
        $customer = $this->customer();

        foreach (['devices', 'consents', 'saved-places', 'deletion-requests', 'messages', 'audit'] as $tab) {
            $this->get(route("admin.customers.tabs.{$tab}", $customer))->assertOk()->assertSee('data-admin-table', false);
            $this->datatable(route("admin.customers.tabs.{$tab}", $customer))->assertOk()->assertJsonStructure(['data', 'recordsTotal']);
        }
    }

    public function test_customers_cannot_be_hard_deleted_from_the_panel(): void
    {
        $this->actingAsAdmin(array_map(static fn (AdminPermission $p): AdminPermission => $p, AdminPermission::cases()));
        $customer = $this->customer();

        $this->deleteJson('/admin/customers/'.$customer->uuid)->assertStatus(405);
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.customers.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('delete', $customer));
        $this->assertNotNull(User::query()->find($customer->id));
    }
}
