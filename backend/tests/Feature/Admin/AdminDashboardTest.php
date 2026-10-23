<?php

namespace Tests\Feature\Admin;

use App\Enums\Admin\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_the_dashboard_needs_the_permission(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.dashboard')));

        $this->actingAsAdmin();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_home_goes_to_the_dashboard_when_permitted_else_to_the_first_menu_item_else_an_empty_page(): void
    {
        $this->actingAsAdmin([AdminPermission::DashboardView]);
        $this->get(route('admin.home'))->assertRedirect(route('admin.dashboard'));

        $this->actingAsAdmin([AdminPermission::AuditLogsView]);
        $this->get(route('admin.home'))->assertRedirect(route('admin.audit-logs.index'));

        $this->actingAsAdmin([AdminPermission::BookingsView]);
        $this->get(route('admin.home'))->assertRedirect(route('admin.bookings.index'));

        $this->actingAsAdmin();
        $this->get(route('admin.home'), ['Accept-Language' => 'en'])->assertOk()->assertSee('Nothing to show yet');
    }

    public function test_it_links_only_to_what_the_admin_may_open(): void
    {
        $this->actingAsAdmin([AdminPermission::DashboardView, AdminPermission::BookingsView, AdminPermission::AuditLogsView]);

        $page = $this->get(route('admin.dashboard'), ['Accept-Language' => 'en'])->assertOk();

        $page->assertSee(route('admin.bookings.index'))
            ->assertSee(route('admin.audit-logs.index'))
            ->assertDontSee(route('admin.admins.index'))
            ->assertDontSee(route('admin.cash-reconciliations.index'));
    }

    public function test_the_menu_only_lists_items_the_admin_may_open(): void
    {
        $this->actingAsAdmin([AdminPermission::DashboardView, AdminPermission::RolesView]);

        $this->get(route('admin.dashboard'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee(route('admin.roles.index'))
            ->assertDontSee(route('admin.admins.index'))
            ->assertDontSee(route('admin.audit-logs.index'))
            ->assertDontSee(route('admin.login-activity.index'));
    }
}
