<?php

namespace Tests\Feature\Admin\Kit;

use App\Enums\Admin\AdminExportStatus;
use App\Enums\Admin\AdminPermission;
use App\Http\Middleware\SetLocale;
use App\Models\AdminExport;
use App\Models\Country;
use App\Models\DemoItem;
use App\Support\Admin\AdminMenu;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Shared admin infrastructure: lookups, locale switcher, menu, home, queued exports, guest scaffolding.
 */
class AdminKitTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_lookup_searches_resolves_ids_and_rejects_unknown_lookups(): void
    {
        $this->actingAsAdmin();
        $egypt = Country::query()->where('iso2', 'EG')->firstOrFail();
        Country::factory()->count(25)->create();

        $this->getJson(route('admin.lookups', ['lookup' => 'countries', 'q' => 'مصر']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $egypt->id)
            ->assertJsonPath('data.0.meta.iso2', 'EG');

        $this->getJson(route('admin.lookups', ['lookup' => 'countries']))->assertOk()->assertJsonCount(20, 'data');
        $this->getJson(route('admin.lookups', ['lookup' => 'countries', 'ids' => [$egypt->id]]))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('admin.lookups', ['lookup' => 'secrets']))->assertNotFound()->assertJsonPath('errors._code', 'not_found');
        $this->getJson(route('admin.lookups', ['lookup' => 'countries', 'q' => str_repeat('x', 101)]))->assertStatus(422);
    }

    public function test_locale_switcher_stores_the_locale_and_flips_direction(): void
    {
        $this->actingAsAdmin([AdminPermission::DemoItemsView]);

        $this->precognitive('POST', route('admin.locale.update'), ['locale' => 'fr'])->assertStatus(422);
        $this->postJson(route('admin.locale.update'), ['locale' => 'en'])->assertOk()->assertJsonPath('data.reload', true);
        $this->assertSame('en', session(SetLocale::SESSION_KEY));

        $this->get(route('admin.demo-items.index'), ['Accept-Language' => 'ar'])->assertSee('dir="ltr"', false)->assertSee('lang="en"', false);
    }

    public function test_menu_is_permission_and_environment_aware(): void
    {
        $this->actingAsAdmin();
        $this->assertSame([], app(AdminMenu::class)->visibleGroups());
        $this->get(route('admin.home'))->assertOk()->assertSee(__('admin.layout.home_empty_title'));

        $this->actingAsAdmin([AdminPermission::DemoItemsView]);
        $groups = app(AdminMenu::class)->visibleGroups();
        $this->assertSame('developer', $groups[0]['key']);
        $this->assertSame(route('admin.demo-items.index'), $groups[0]['items'][0]['url']);
        $this->get(route('admin.home'))->assertRedirect(route('admin.demo-items.index'));

        $this->app['env'] = 'production';
        $this->assertSame([], app(AdminMenu::class)->visibleGroups(), 'demo entry hidden outside local/testing');
    }

    public function test_queued_export_generates_a_csv_downloadable_only_by_its_owner(): void
    {
        Storage::fake('local');
        $admin = $this->actingAsAdmin([AdminPermission::DemoItemsView, AdminPermission::DemoItemsExport]);
        DemoItem::factory()->create(['code' => 'EXP-1', 'name' => ['ar' => 'تصدير', 'en' => 'Export']]);
        DemoItem::factory()->create(['code' => '=CMD()']);

        $statusUrl = $this->postJson(route('admin.demo-items.export'), ['filters' => ['search' => 'EXP']])
            ->assertOk()
            ->json('data.status_url');

        $export = AdminExport::query()->sole();
        $this->assertSame(AdminExportStatus::Completed, $export->status, 'sync queue in tests');
        $this->assertSame(1, $export->rows);

        $download = $this->getJson($statusUrl)->assertOk()->assertJsonPath('data.finished', true)->json('data.download_url');
        $csv = $this->get($download)->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('EXP-1', $csv);
        $this->assertStringNotContainsString('CMD', $csv);

        $this->get(route('admin.exports.download', $export))->assertForbidden();

        $other = $this->actingAsAdmin([AdminPermission::DemoItemsExport]);
        $this->assertNotSame($admin->getKey(), $other->getKey());
        $this->getJson($statusUrl)->assertForbidden();
    }

    public function test_formula_cells_are_neutralised_in_exports(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin([AdminPermission::DemoItemsView, AdminPermission::DemoItemsExport]);
        DemoItem::factory()->create(['code' => 'X1', 'name' => ['ar' => '=HYPERLINK("x")']]);

        $this->postJson(route('admin.demo-items.export'))->assertOk();
        $export = AdminExport::query()->sole();

        $this->assertStringContainsString("'=HYPERLINK", (string) Storage::disk('local')->get((string) $export->path));
    }
}
