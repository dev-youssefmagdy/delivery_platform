<?php

namespace Tests\Feature\Admin\Markets;

use App\DataTables\Admin\Markets\CountriesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingKey;
use App\Models\AdminAuditLog;
use App\Models\Country;
use App\Models\Currency;
use App\Services\Settings\SettingsService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Countries.
 */
class CountryAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.countries.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $country = Country::query()->firstOrFail();

        $this->get(route('admin.countries.index'))->assertForbidden();
        $this->getJson(route('admin.countries.show', $country))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.countries.index'))->assertForbidden();
        $this->postJson(route('admin.countries.store'), $this->payload())->assertForbidden();
        $this->deleteJson(route('admin.countries.destroy', $country))->assertForbidden();
    }

    public function test_index_renders_the_listing_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);

        $this->get(route('admin.countries.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.CountriesDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.CountriesDataTable::TABLE_ID.'"', false)
            ->assertSee(route('admin.countries.create'));
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $usd = Currency::factory()->create(['code' => 'USD']);
        $other = Country::factory()->create(['iso2' => 'US', 'name' => ['ar' => 'أمريكا', 'en' => 'Needleland'], 'currency_code' => $usd->code, 'active' => false]);

        $this->datatable(route('admin.countries.index'))->assertOk()->assertJsonPath('recordsTotal', 2);

        $this->datatable(route('admin.countries.index'), ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
        $this->datatable(route('admin.countries.index'), ['active' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
        $this->datatable(route('admin.countries.index'), ['active' => '1'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable(route('admin.countries.index'), ['currency' => 'usd'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
        $this->datatable(route('admin.countries.index'), ['created_at' => ['from' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);

        $this->precognitive('POST', route('admin.countries.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['iso2', 'name.ar', 'phone_code', 'currency_code', 'timezone', 'default_locale', 'active']);

        $this->precognitive('POST', route('admin.countries.store'), $this->payload(['timezone' => 'Mars/Base', 'phone_code' => '20']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['timezone', 'phone_code']);

        $this->precognitive('POST', route('admin.countries.store'), $this->payload())->assertNoContent();
        $this->assertDatabaseCount('countries', 1);
    }

    public function test_store_persists_and_writes_an_audit_row(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);

        $this->postJson(route('admin.countries.store'), $this->payload(['iso2' => 'sa']))->assertOk()->assertJsonPath('success', true);

        $country = Country::query()->where('iso2', 'SA')->firstOrFail();
        $this->assertSame('Saudi Arabia', $country->getTranslation('name', 'en'));
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'country', 'auditable_id' => (string) $country->id]);
    }

    public function test_duplicate_iso_is_rejected(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);

        $this->postJson(route('admin.countries.store'), $this->payload(['iso2' => 'EG']))->assertStatus(422)->assertJsonValidationErrors(['iso2']);
    }

    public function test_update_audits_locks_versions_and_keeps_the_iso_immutable(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $country = Country::factory()->create(['iso2' => 'SA']);
        $update = $this->payload(['name' => ['ar' => 'السعودية', 'en' => 'KSA']]);
        unset($update['iso2']);

        $this->putJson(route('admin.countries.update', $country), $update + ['lock_version' => 0])->assertOk();
        $country->refresh();
        $this->assertSame(1, $country->lock_version);
        $this->assertSame('SA', $country->iso2);
        $this->assertTrue(AdminAuditLog::query()->where('action', AuditAction::Updated->value)->where('auditable_id', (string) $country->id)->exists());

        $this->putJson(route('admin.countries.update', $country), $update + ['lock_version' => 0])
            ->assertStatus(409)
            ->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_the_default_country_cannot_be_deactivated_or_deleted(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $egypt = Country::query()->where('iso2', 'EG')->firstOrFail();

        $this->putJson(route('admin.countries.update', $egypt), $this->payload(['active' => '0']) + ['lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'country_is_default');
        $this->deleteJson(route('admin.countries.destroy', $egypt), ['reason' => 'cleanup please'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'country_is_default');

        // Once another country is the default, Egypt can be removed.
        $other = Country::factory()->create(['iso2' => 'SA']);
        app(SettingsService::class)->set(SettingKey::DefaultCountry, $other->iso2);
        $this->deleteJson(route('admin.countries.destroy', $egypt), ['reason' => 'cleanup please'])->assertOk();
        $this->assertModelMissing($egypt);
    }

    public function test_a_country_needs_an_active_currency(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $inactive = Currency::factory()->create(['code' => 'SAR', 'active' => false]);

        $this->postJson(route('admin.countries.store'), $this->payload(['iso2' => 'SA', 'currency_code' => $inactive->code]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['currency_code']);
    }

    public function test_delete_writes_an_audit_row_with_the_reason(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $country = Country::factory()->create();

        $this->deleteJson(route('admin.countries.destroy', $country), ['reason' => 'No longer served'])
            ->assertOk()
            ->assertJsonPath('data.reload_table', [CountriesDataTable::TABLE_ID]);

        $this->assertModelMissing($country);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $country->id, 'reason' => 'No longer served']);
    }

    public function test_show_create_and_edit_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $country = Country::query()->firstOrFail();

        $this->get(route('admin.countries.show', $country))->assertOk()->assertSee('Africa/Cairo');
        $this->get(route('admin.countries.create'))->assertOk()->assertSee('name="iso2"', false);
        $this->get(route('admin.countries.edit', $country))->assertOk()->assertSee('name="lock_version"', false)->assertDontSee('name="iso2"', false);
        $this->get(route('admin.countries.activity', $country))->assertOk();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return $override + [
            'iso2' => 'SA',
            'name' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
            'phone_code' => '+966',
            'currency_code' => 'EGP',
            'timezone' => 'Asia/Riyadh',
            'default_locale' => 'ar',
            'active' => '1',
        ];
    }
}
