<?php

namespace Tests\Feature\Admin\Markets;

use App\DataTables\Admin\Markets\CurrenciesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Models\Country;
use App\Models\Currency;
use App\Support\Money\CurrencyRegistry;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Currencies, plus the money-integrity rules (ADR-04).
 */
class CurrencyAdminTest extends TestCase
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
        $this->assertGuestBlocked($this->get(route('admin.currencies.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $currency = Currency::query()->firstOrFail();

        $this->get(route('admin.currencies.index'))->assertForbidden();
        $this->getJson(route('admin.currencies.show', $currency))->assertForbidden();
        $this->datatable(route('admin.currencies.index'))->assertForbidden();
        $this->postJson(route('admin.currencies.store'), $this->payload())->assertForbidden();
    }

    public function test_index_renders_the_listing_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);

        $this->get(route('admin.currencies.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.CurrenciesDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.CurrenciesDataTable::TABLE_ID.'"', false);
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $kwd = Currency::factory()->create(['code' => 'KWD', 'exponent' => 3, 'symbol_en' => 'Needle', 'active' => false]);

        $this->datatable(route('admin.currencies.index'))->assertJsonPath('recordsTotal', 2);
        $this->datatable(route('admin.currencies.index'), ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $kwd->id);
        $this->datatable(route('admin.currencies.index'), ['active' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $kwd->id);
        $this->datatable(route('admin.currencies.index'), ['created_at' => ['to' => now()->subDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);

        $this->precognitive('POST', route('admin.currencies.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'exponent', 'symbol_ar', 'symbol_en', 'active']);
        $this->precognitive('POST', route('admin.currencies.store'), $this->payload(['code' => 'EGP', 'exponent' => '7']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'exponent']);

        $this->precognitive('POST', route('admin.currencies.store'), $this->payload())->assertNoContent();
        $this->assertDatabaseCount('currencies', 1);
    }

    public function test_store_persists_audits_and_the_registry_sees_the_new_currency(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $registry = app(CurrencyRegistry::class);
        $this->assertFalse($registry->has('SAR'));

        $this->postJson(route('admin.currencies.store'), $this->payload(['code' => 'sar']))->assertOk();

        $currency = Currency::query()->where('code', 'SAR')->firstOrFail();
        $this->assertSame(2, $registry->get('SAR')->exponent);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'currency', 'auditable_id' => (string) $currency->id]);
    }

    public function test_update_changes_symbols_but_never_code_or_exponent(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $currency = Currency::factory()->create(['code' => 'SAR', 'exponent' => 2]);

        $this->putJson(route('admin.currencies.update', $currency), [
            'code' => 'XXX', 'exponent' => '3', 'symbol_ar' => 'ر.س', 'symbol_en' => 'SAR', 'active' => '1', 'lock_version' => 0,
        ])->assertOk();

        $currency->refresh();
        $this->assertSame('SAR', $currency->code);
        $this->assertSame(2, $currency->exponent);
        $this->assertSame('ر.س', $currency->symbol_ar);
        $this->assertSame(1, $currency->lock_version);

        $this->putJson(route('admin.currencies.update', $currency), ['symbol_ar' => 'x', 'symbol_en' => 'y', 'active' => '1', 'lock_version' => 0])
            ->assertStatus(409)
            ->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_a_used_or_default_currency_cannot_be_deactivated_or_deleted(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $egp = Currency::query()->where('code', 'EGP')->firstOrFail();

        $this->putJson(route('admin.currencies.update', $egp), ['symbol_ar' => 'ج.م', 'symbol_en' => 'EGP', 'active' => '0', 'lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'currency_in_use');
        $this->deleteJson(route('admin.currencies.destroy', $egp), ['reason' => 'remove it please'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'currency_in_use');

        $unused = Currency::factory()->create();
        $this->deleteJson(route('admin.currencies.destroy', $unused), ['reason' => 'unused currency'])->assertOk();
        $this->assertModelMissing($unused);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'reason' => 'unused currency']);

        $this->assertSame(1, Country::query()->count());
    }

    public function test_show_create_and_edit_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::MarketsManage]);
        $currency = Currency::query()->firstOrFail();

        $this->get(route('admin.currencies.show', $currency))->assertOk()->assertSee('EGP');
        $this->get(route('admin.currencies.create'))->assertOk()->assertSee('name="exponent"', false);
        $this->get(route('admin.currencies.edit', $currency))->assertOk()->assertSee('name="lock_version"', false)->assertDontSee('name="exponent"', false);
        $this->get(route('admin.currencies.activity', $currency))->assertOk();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return $override + ['code' => 'SAR', 'exponent' => '2', 'symbol_ar' => 'ر.س', 'symbol_en' => 'SAR', 'active' => '1'];
    }
}
