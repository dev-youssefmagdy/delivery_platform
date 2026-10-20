<?php

namespace Tests\Feature\Admin\Identity;

use App\DataTables\Admin\Identity\OtpChallengesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Admin\AdminRole;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpDeliveryStatus;
use App\Enums\Identity\OtpPurpose;
use App\Models\OtpChallenge;
use App\Support\Auth\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for the read-only OTP challenges log.
 */
class OtpChallengesAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $attributes
     */
    private function challenge(string $phone, array $attributes = []): OtpChallenge
    {
        return OtpChallenge::query()->create($attributes + [
            'phone_hash' => PhoneNumber::hash($phone), 'phone_masked' => PhoneNumber::mask($phone), 'purpose' => OtpPurpose::Login,
            'app' => ClientApp::Customer, 'code_hash' => str_repeat('a', 64), 'attempts' => 0, 'max_attempts' => 5,
            'delivery_status' => OtpDeliveryStatus::Sent, 'delivery_driver' => 'log', 'expires_at' => now()->addMinutes(5),
            'resend_available_at' => now()->addMinute(), 'ip' => '10.0.0.1',
        ]);
    }

    public function test_guests_are_redirected_and_users_without_permission_get_403(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.otp-challenges.index')));

        $this->actingAsAdmin([AdminPermission::LoginActivityView]);
        $this->get(route('admin.otp-challenges.index'))->assertForbidden();
        $this->datatable(route('admin.otp-challenges.index'))->assertForbidden();
    }

    public function test_the_page_has_a_collapsed_filters_card_and_is_read_only(): void
    {
        $this->actingAsAdmin([AdminPermission::OtpChallengesView]);

        $this->get(route('admin.otp-challenges.index'))->assertOk()
            ->assertSee('data-filters-for="'.OtpChallengesDataTable::TABLE_ID.'"', false)
            ->assertSee('aria-expanded="false"', false);
        $this->postJson(route('admin.otp-challenges.index'))->assertStatus(405);
    }

    public function test_datatable_respects_every_filter_and_never_exposes_codes_or_numbers(): void
    {
        $this->actingAsAdmin([AdminPermission::OtpChallengesView]);
        $match = $this->challenge('+201001234567', ['attempts' => 5, 'ip' => '10.0.0.7', 'delivery_status' => OtpDeliveryStatus::Failed, 'app' => ClientApp::Provider]);
        $this->challenge('+201007654321', ['consumed_at' => now()]);
        $this->challenge('+201007654322', ['expires_at' => now()->subMinute()]);
        $url = route('admin.otp-challenges.index');

        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 3);
        $this->datatable($url, ['phone' => '+20 100 123 4567'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['states' => ['locked']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['states' => ['verified']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['states' => ['expired']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['states' => ['pending']])->assertJsonPath('recordsFiltered', 0);
        $this->datatable($url, ['states' => ['locked', 'verified']])->assertJsonPath('recordsFiltered', 2);
        $this->datatable($url, ['deliveries' => ['failed']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['apps' => ['provider']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['ip' => '10.0.0.7'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['created_at' => ['from' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);

        $body = (string) $this->datatable($url)->getContent();
        $this->assertStringNotContainsString('code_hash', $body);
        $this->assertStringNotContainsString('+201001234567', $body);
        $this->assertSame('+20••••••4567', $this->datatable($url, ['phone' => '+201001234567'])->json('data.0.phone_masked'));
    }

    public function test_only_the_system_integration_role_gets_the_permission_by_default(): void
    {
        $this->assertContains(AdminPermission::OtpChallengesView, AdminRole::SystemIntegrationAdmin->defaultPermissions());
        $this->assertNotContains(AdminPermission::OtpChallengesView, AdminRole::ReadOnlyAuditor->defaultPermissions());
    }
}
