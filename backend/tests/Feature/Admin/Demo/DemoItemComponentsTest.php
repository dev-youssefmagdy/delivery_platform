<?php

namespace Tests\Feature\Admin\Demo;

use App\DataTables\Admin\Demo\DemoItemsDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Demo\DemoItemStatus;
use App\Enums\Demo\DemoItemTag;
use App\Enums\Demo\DemoItemType;
use App\Models\Country;
use App\Models\DemoItem;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * The Demo Item proof module exercises every UI-kit component end to end (P0.3 reference tests).
 */
class DemoItemComponentsTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    /** @var list<AdminPermission> */
    private array $all = [AdminPermission::DemoItemsView, AdminPermission::DemoItemsManage, AdminPermission::DemoItemsExport];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_create_page_renders_every_form_component_in_arabic_rtl_and_english_ltr(): void
    {
        $this->actingAsAdmin($this->all);

        $ar = $this->get(route('admin.demo-items.create'), ['Accept-Language' => 'ar'])->assertOk();
        $ar->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false)->assertSee('الاسم');

        foreach ([
            'data-ajax-form', 'name="name[ar]"', 'name="name[en]"', 'name="description[ar]"', 'data-enhanced-select',
            'data-remote-select', 'role="switch"', 'type="checkbox"', 'type="radio"', 'data-datepicker="date"',
            'data-datepicker="datetime"', 'data-range-end', 'x-data="adminMoney(2)"', 'type="file"', 'data-map-point',
            'data-map-polygon', 'name="specs[__INDEX__][key]"', 'name="options[__INDEX__][label]"', 'data-error-for="name.ar"',
            'data-error-summary',
        ] as $marker) {
            $ar->assertSee($marker, false);
        }

        $this->get(route('admin.demo-items.create'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Campaign period');
    }

    public function test_index_has_collapsed_filters_prefilled_from_the_query_string_stats_and_quick_create_modal(): void
    {
        $this->actingAsAdmin($this->all);
        DemoItem::factory()->count(2)->create(['status' => DemoItemStatus::Active]);

        $response = $this->get(route('admin.demo-items.index', ['filters' => ['search' => 'abc', 'featured' => '1']]))->assertOk();

        $response->assertSee('x-data="filtersCard(', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('value="abc"', false)
            ->assertSee('id="demo-items-quick-create"', false)
            ->assertSee('data-export-url', false)
            ->assertSee('data-admin-table', false);
        $this->assertMatchesRegularExpression('/name="filters\[featured\]" value="1"[^>]*checked/', (string) $response->getContent());
    }

    public function test_store_with_every_field_persists_typed_values(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin($this->all);
        $country = Country::query()->firstOrFail();

        $this->post(route('admin.demo-items.store'), $this->fullPayload($country->id) + [
            'cover' => UploadedFile::fake()->image('cover.jpg', 200, 200),
        ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $item = DemoItem::query()->sole();
        $this->assertSame('DEMO-1', $item->code);
        $this->assertSame(DemoItemType::Premium, $item->type);
        $this->assertSame(1999, $item->price->amount);
        $this->assertSame('EGP', $item->price->currency->code);
        $this->assertSame($country->id, $item->country_id);
        $this->assertEquals([DemoItemTag::New, DemoItemTag::Limited], $item->tags?->all());
        $this->assertTrue($item->is_featured);
        $this->assertSame('2026-11-01', $item->available_on?->toDateString());
        $this->assertSame('2026-11-01 08:30', $item->published_at?->utc()->format('Y-m-d H:i'), 'Cairo 10:30 → UTC 08:30');
        $this->assertCount(3, $item->area ?? []);
        $this->assertSame(['color' => 'red'], $item->specs);
        $this->assertSame([['label' => 'Gift wrap', 'price' => 500, 'is_default' => true]], $item->options);
        $this->assertNotNull($item->cover_path);
        Storage::disk('public')->assertExists((string) $item->cover_path);
    }

    public function test_precognition_maps_nested_errors_and_validates_a_single_field(): void
    {
        $this->actingAsAdmin($this->all);
        $payload = $this->fullPayload(null);
        $payload['options'][3] = ['label' => '', 'price' => 'x'];
        $payload['area'] = json_encode([[1, 2]]);
        $payload['ends_on'] = '2026-01-01';

        $this->precognitive('POST', route('admin.demo-items.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['options.3.label', 'options.3.price', 'area', 'ends_on']);

        $this->precognitive('POST', route('admin.demo-items.store'), ['code' => 'bad code!'], only: 'code')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code'])
            ->assertJsonMissingValidationErrors(['name.ar', 'price']);

        $this->precognitive('POST', route('admin.demo-items.store'), ['code' => 'GOOD-1'], only: 'code')->assertNoContent();
        $this->assertDatabaseCount('demo_items', 0);
    }

    public function test_quick_create_closes_the_modal_resets_the_form_and_reloads_the_table(): void
    {
        $this->actingAsAdmin($this->all);

        $this->postJson(route('admin.demo-items.store'), [
            'quick' => 1,
            'name' => ['ar' => 'سريع'],
            'code' => 'quick-1',
            'type' => DemoItemType::Bundle->value,
            'status' => DemoItemStatus::Active->value,
            'price' => 100,
        ])->assertOk()
            ->assertJsonPath('data.close_modal', true)
            ->assertJsonPath('data.reset', true)
            ->assertJsonPath('data.reload_table', [DemoItemsDataTable::TABLE_ID]);

        $this->assertSame('QUICK-1', DemoItem::query()->sole()->code);
    }

    public function test_archive_requires_a_reason_and_records_it(): void
    {
        $this->actingAsAdmin($this->all);
        $item = DemoItem::factory()->create(['status' => DemoItemStatus::Active]);

        $this->precognitive('POST', route('admin.demo-items.archive', $item), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.demo-items.archive', $item), ['reason' => 'no'])->assertStatus(422);

        $this->postJson(route('admin.demo-items.archive', $item), ['reason' => 'Replaced by a new item'])
            ->assertOk()
            ->assertJsonPath('data.reload_table', [DemoItemsDataTable::TABLE_ID]);

        $item->refresh();
        $this->assertSame(DemoItemStatus::Archived, $item->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $item->id, 'reason' => 'Replaced by a new item']);

        // Archived items can be neither archived again nor edited (policy).
        $this->postJson(route('admin.demo-items.archive', $item), ['reason' => 'Again please'])->assertForbidden();
        $this->get(route('admin.demo-items.edit', $item))->assertForbidden();
    }

    public function test_datatable_applies_every_filter_type(): void
    {
        $this->actingAsAdmin([AdminPermission::DemoItemsView]);
        $egypt = Country::query()->where('iso2', 'EG')->firstOrFail();
        $match = DemoItem::factory()->featured()->create([
            'type' => DemoItemType::Premium, 'status' => DemoItemStatus::Active, 'price_minor' => 5000, 'country_id' => $egypt->id,
        ]);
        DemoItem::factory()->create(['type' => DemoItemType::Standard, 'price_minor' => 100]);
        $url = route('admin.demo-items.index');

        foreach ([
            ['type' => 'premium'],
            ['statuses' => ['active']],
            ['featured' => '1'],
            ['country' => [$egypt->id]],
            ['price' => ['min' => '40', 'max' => '60.50']],
            ['search' => $match->code],
        ] as $filters) {
            $this->datatable($url, $filters)->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        }

        $this->datatable($url, ['featured' => '0'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['type' => 'not-a-type', 'price' => ['min' => 'abc']])->assertJsonPath('recordsFiltered', 2);
    }

    public function test_datatable_enforces_the_ordering_and_search_allowlist(): void
    {
        $this->actingAsAdmin([AdminPermission::DemoItemsView]);
        DemoItem::factory()->count(3)->create();
        config(['app.debug' => true]);

        // Column 1 (name, JSON) is not orderable; a forged column definition must not reach the query.
        $this->datatable(route('admin.demo-items.index'), [], [
            'order' => [['column' => 1, 'dir' => 'asc'], ['column' => 0, 'dir' => 'sideways']],
            'columns' => [['data' => 'id', 'name' => 'id) or 1=1 --', 'orderable' => 'true', 'searchable' => 'true', 'search' => ['value' => 'x']]],
        ])->assertOk()->assertJsonPath('recordsFiltered', 3)->assertJsonMissingPath('queries')->assertJsonMissingPath('input');
    }

    public function test_show_quick_view_and_activity_render(): void
    {
        $this->actingAsAdmin($this->all);
        $item = DemoItem::factory()->create(['latitude' => '30.0444000', 'longitude' => '31.2357000', 'specs' => ['size' => 'L']]);
        $this->putJson(route('admin.demo-items.update', $item), $this->fullPayload(null) + ['lock_version' => 0])->assertOk();

        $this->get(route('admin.demo-items.show', $item))->assertOk()
            ->assertSee('data-map-preview', false)
            ->assertSee('data-tab-url="'.route('admin.demo-items.activity', $item).'"', false)
            ->assertSee('data-confirm-action', false)
            ->assertSee('data-reason-required="true"', false);

        $this->get(route('admin.demo-items.quick-view', $item))->assertOk()->assertSee('DEMO-1')->assertDontSee('<html', false);
        $this->get(route('admin.demo-items.activity', $item))->assertOk()->assertSee(AuditAction::Updated->label());
    }

    public function test_the_demo_module_is_unavailable_when_disabled(): void
    {
        config(['admin.demo.enabled' => false]);
        $this->actingAsAdmin($this->all);

        $this->get(route('admin.demo-items.index'))->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function fullPayload(?int $countryId): array
    {
        return [
            'name' => ['ar' => 'عنصر كامل', 'en' => 'Full item'],
            'description' => ['ar' => 'وصف', 'en' => ''],
            'code' => 'demo-1',
            'type' => DemoItemType::Premium->value,
            'status' => DemoItemStatus::Active->value,
            'price' => 1999,
            'country_id' => $countryId,
            'tags' => [DemoItemTag::New->value, DemoItemTag::Limited->value],
            'is_featured' => '1',
            'is_public' => '0',
            'available_on' => '2026-11-01',
            'published_at' => '2026-11-01 10:30',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'latitude' => '30.0444',
            'longitude' => '31.2357',
            'area' => json_encode([[30.1, 31.2], [30.2, 31.3], [30.0, 31.4]]),
            'specs' => [5 => ['key' => 'color', 'value' => 'red']],
            'options' => [0 => ['label' => 'Gift wrap', 'price' => '500', 'is_default' => '1']],
            'notes' => 'Internal',
        ];
    }
}
