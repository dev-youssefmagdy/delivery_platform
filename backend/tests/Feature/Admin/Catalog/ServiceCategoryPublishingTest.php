<?php

namespace Tests\Feature\Admin\Catalog;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Content\ContentStatus;
use App\Enums\Settings\SettingKey;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Models\ServiceFamily;
use App\Models\ServiceFormSchema;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\Catalog\CatalogUsageRepositoryInterface;
use App\Services\Catalog\CategoryPolicyResolver;
use App\Services\Catalog\ServiceCategoryPublisher;
use App\Services\Settings\SettingsService;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Form-schema builder, publishing, immutable versions, activation and the deactivation guards (Plan P1.4 steps 3-4).
 */
class ServiceCategoryPublishingTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurrencySeeder::class);
    }

    private function category(bool $withVehicle = true): ServiceCategory
    {
        $category = ServiceCategory::factory()->create();

        if ($withVehicle) {
            $category->vehicleCategories()->attach(VehicleCategory::factory()->create());
        }

        return $category;
    }

    /**
     * @return array<string, mixed>
     */
    private function builderRow(array $override = []): array
    {
        return $override + ['key' => 'passengers', 'type' => 'integer', 'label_ar' => 'عدد الركاب', 'label_en' => 'Passengers', 'required' => '1', 'min' => '1', 'max' => '4'];
    }

    public function test_the_builder_saves_a_valid_draft_schema_and_audits_it(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();

        $this->putJson(route('admin.service-categories.form-schema.update', $category), [
            'lock_version' => 0,
            'fields' => [
                $this->builderRow(),
                $this->builderRow(['key' => 'kind', 'type' => 'select', 'min' => '', 'max' => '', 'options_text' => "a|أ|A\nb|ب"]),
                $this->builderRow(['key' => 'extra', 'type' => 'text', 'min' => '', 'max' => '', 'required' => '0', 'visible_field' => 'kind', 'visible_op' => 'in', 'visible_value' => 'a, b']),
            ],
        ])->assertOk();

        $draft = $category->draftSchema()->firstOrFail();
        $this->assertSame(1, $draft->lock_version);
        $this->assertSame(['passengers', 'kind', 'extra'], array_column($draft->fields, 'key'));
        $this->assertSame(['a', 'b'], array_column($draft->fields[1]['options'], 'value'));
        $this->assertSame('أ', $draft->fields[1]['options'][0]['label']['ar']);
        $this->assertEquals(['field' => 'kind', 'op' => 'in', 'value' => ['a', 'b']], $draft->fields[2]['visible_if']);
        $this->assertTrue($draft->fields[0]['required']);
        $this->assertSame(1, $draft->fields[0]['min']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_id' => (string) $category->id]);
    }

    public function test_the_builder_shows_invalid_schema_errors_inline_on_the_right_cells(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();

        $this->precognitive('PUT', route('admin.service-categories.form-schema.update', $category), [
            'lock_version' => 0,
            'fields' => [
                3 => $this->builderRow(['key' => 'dup']),
                7 => $this->builderRow(['key' => 'dup', 'type' => 'select']),
                9 => $this->builderRow(['key' => 'later', 'min' => '5', 'max' => '2', 'visible_field' => 'nope', 'visible_op' => 'eq', 'visible_value' => '1']),
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['fields.7.key', 'fields.7.options_text', 'fields.9.max', 'fields.9.visible_field']);

        $this->assertSame([], $category->draftSchema()->firstOrFail()->fields);
    }

    public function test_the_builder_rejects_stale_submits(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();
        $payload = ['lock_version' => 0, 'fields' => [$this->builderRow()]];

        $this->putJson(route('admin.service-categories.form-schema.update', $category), $payload)->assertOk();
        $this->putJson(route('admin.service-categories.form-schema.update', $category), $payload)->assertStatus(409)->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_publishing_snapshots_the_category_and_creates_an_immutable_version(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();
        $category->update(['offer_ttl_seconds' => 45, 'max_passengers' => 4]);
        $this->putJson(route('admin.service-categories.form-schema.update', $category), ['lock_version' => 0, 'fields' => [$this->builderRow()]])->assertOk();

        $this->postJson(route('admin.service-categories.publish', $category), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'First release'])->assertOk();

        $category->refresh();
        $version = $category->publishedVersion()->firstOrFail();
        $this->assertSame(1, $version->version);
        $this->assertSame($admin->id, $version->published_by_admin_id);
        $this->assertSame('First release', $version->reason);
        $this->assertSame(45, $version->snapshot['dispatch']['offer_ttl_seconds']);
        $this->assertSame(app(SettingsService::class)->int(SettingKey::BookingSearchTtlSeconds), $version->snapshot['dispatch']['search_ttl_seconds']);
        $this->assertSame(['passengers'], array_column($version->snapshot['form_schema']['fields'], 'key'));
        $this->assertCount(1, $version->snapshot['vehicle_categories']);
        $this->assertSame(1, $version->formSchema->version);
        $this->assertSame(ContentStatus::Published, $version->formSchema->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Published->value, 'auditable_id' => (string) $category->id, 'reason' => 'First release']);
    }

    public function test_publishing_requires_vehicles_and_a_valid_schema_and_real_changes(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category(withVehicle: false);

        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'First release'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'category_requires_vehicle_category');

        $category->vehicleCategories()->attach(VehicleCategory::factory()->create());
        $category->draftSchema()->firstOrFail()->update(['fields' => [['key' => 'BAD', 'type' => 'text', 'label' => ['ar' => 'x']]]]);
        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'First release'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'form_schema_invalid');

        $category->draftSchema()->firstOrFail()->update(['fields' => []]);
        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'First release'])->assertOk();
        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'Again'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'category_no_changes');
        $this->assertSame(1, ServiceCategoryVersion::query()->count());
    }

    public function test_editing_after_publish_never_changes_published_versions(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();
        $this->putJson(route('admin.service-categories.form-schema.update', $category), ['lock_version' => 0, 'fields' => [$this->builderRow()]])->assertOk();
        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'First release'])->assertOk();

        $v1 = ServiceCategoryVersion::query()->firstOrFail();
        $v1Snapshot = $v1->snapshot;
        $v1Schema = $v1->formSchema->fields;

        // Edit the draft configuration and the schema.
        $category->refresh();
        $category->update(['max_passengers' => 7, 'name' => ['ar' => 'اسم جديد', 'en' => 'New name']]);
        $this->putJson(route('admin.service-categories.form-schema.update', $category), ['lock_version' => 1, 'fields' => [$this->builderRow(['max' => '7']), $this->builderRow(['key' => 'notes', 'type' => 'textarea', 'min' => '', 'max' => '', 'required' => '0'])]])->assertOk();

        $this->assertEquals($v1Snapshot, $v1->fresh()->snapshot);
        $this->assertSame($v1Schema, $v1->fresh()->formSchema->fields);
        $this->assertTrue(app(ServiceCategoryPublisher::class)->hasUnpublishedChanges($category->fresh(['publishedVersion'])));

        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'Added notes'])->assertOk();
        $category->refresh();
        $v2 = $category->publishedVersion()->firstOrFail();
        $this->assertSame(2, $v2->version);
        $this->assertSame(7, $v2->snapshot['limits']['max_passengers']);
        $this->assertCount(2, $v2->snapshot['form_schema']['fields']);
        $this->assertEquals($v1Snapshot, $v1->fresh()->snapshot);
        $this->assertSame(ContentStatus::Archived, $v1->formSchema()->firstOrFail()->status);
        $this->assertSame($v1Schema, $v1->formSchema()->firstOrFail()->fields);
        $this->assertFalse(app(ServiceCategoryPublisher::class)->hasUnpublishedChanges($category->fresh(['publishedVersion'])));
    }

    public function test_versions_and_published_schemas_are_immutable_at_the_model_level(): void
    {
        $category = $this->category();
        app(ServiceCategoryPublisher::class)->publish($category, 'First release', null);
        $version = ServiceCategoryVersion::query()->firstOrFail();
        $schema = $version->formSchema;

        $this->assertThrows(fn () => $version->update(['reason' => 'tampered']), LogicException::class);
        $this->assertThrows(fn () => $version->delete(), LogicException::class);
        $this->assertThrows(fn () => $schema->update(['fields' => [['key' => 'x']]]), LogicException::class);
        $this->assertThrows(fn () => $schema->delete(), LogicException::class);
        $fresh = $schema->fresh();
        $fresh->update(['status' => ContentStatus::Archived]);
        $this->assertSame(ContentStatus::Archived, $schema->fresh()->status);
    }

    public function test_the_versions_tab_lists_published_versions(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();
        $this->get(route('admin.service-categories.versions', $category))->assertOk()->assertSee(__('admin.service_categories.versions.empty'));

        app(ServiceCategoryPublisher::class)->publish($category, 'First release', null);
        $this->get(route('admin.service-categories.versions', $category))->assertOk()->assertSee('First release');
    }

    public function test_activation_requires_a_published_version_and_an_active_family(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = $this->category();

        $this->postJson(route('admin.service-categories.activate', $category))
            ->assertStatus(422)->assertJsonPath('errors._code', 'category_requires_published_version');

        app(ServiceCategoryPublisher::class)->publish($category, 'First release', null);
        $category->family->update(['active' => false]);
        $this->postJson(route('admin.service-categories.activate', $category))
            ->assertStatus(422)->assertJsonPath('errors._code', 'category_family_inactive');

        $category->family->update(['active' => true]);
        $this->postJson(route('admin.service-categories.activate', $category))->assertOk();
        $this->assertTrue($category->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $category->id]);
    }

    public function test_the_last_active_category_of_the_platform_cannot_be_deactivated(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $only = ServiceCategory::factory()->active()->create();

        $this->postJson(route('admin.service-categories.deactivate', $only), ['reason' => 'No longer needed'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'catalog_last_active_category');
        $this->assertTrue($only->refresh()->active);

        $other = ServiceCategory::factory()->active()->create();
        $this->postJson(route('admin.service-categories.deactivate', $only), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.service-categories.deactivate', $only), ['reason' => 'No longer needed'])->assertOk();
        $this->assertFalse($only->refresh()->active);
        $this->assertTrue($other->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $only->id, 'reason' => 'No longer needed']);
    }

    public function test_deactivation_with_active_bookings_needs_an_explicit_confirmation(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        ServiceCategory::factory()->active()->create();
        $busy = ServiceCategory::factory()->active()->create();
        $this->app->bind(CatalogUsageRepositoryInterface::class, fn () => new class implements CatalogUsageRepositoryInterface
        {
            public function activeBookingCount(ServiceCategory $category): int
            {
                return 3;
            }
        });

        $this->postJson(route('admin.service-categories.deactivate', $busy), ['reason' => 'Retiring the service'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'category_has_active_bookings')
            ->assertJsonValidationErrors(['confirm_active_bookings']);
        $this->assertTrue($busy->refresh()->active);

        $this->postJson(route('admin.service-categories.deactivate', $busy), ['reason' => 'Retiring the service', 'confirm_active_bookings' => '1'])->assertOk();
        $this->assertFalse($busy->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $busy->id]);
        $log = \App\Models\AdminAuditLog::query()->where('auditable_id', (string) $busy->id)->where('action', AuditAction::StatusChanged->value)->firstOrFail();
        $this->assertSame(3, $log->meta['active_bookings']);
    }

    public function test_null_policy_fields_fall_back_to_platform_settings(): void
    {
        $category = ServiceCategory::factory()->create(['offer_ttl_seconds' => null, 'search_ttl_seconds' => 200, 'dispatch_radius_steps_km' => null, 'dispatch_batch_size' => null, 'pin_required' => null]);
        $settings = app(SettingsService::class);
        $settings->set(SettingKey::OfferTtlSeconds, 90);
        $settings->set(SettingKey::RidePinRequired, false);
        $settings->set(SettingKey::DispatchRadiusStepsKm, [1.5, 3.0]);

        $policy = app(CategoryPolicyResolver::class)->resolve($category);

        $this->assertSame(90, $policy->offerTtlSeconds);
        $this->assertSame(200, $policy->searchTtlSeconds);
        $this->assertSame([1.5, 3.0], $policy->dispatchRadiusStepsKm);
        $this->assertSame($settings->int(SettingKey::DispatchBatchSize), $policy->dispatchBatchSize);
        $this->assertFalse($policy->pinRequired);

        $category->update(['pin_required' => true, 'offer_ttl_seconds' => 30, 'dispatch_radius_steps_km' => [5, 9], 'dispatch_batch_size' => 4]);
        $own = app(CategoryPolicyResolver::class)->resolve($category->fresh());
        $this->assertSame([30, true, 4], [$own->offerTtlSeconds, $own->pinRequired, $own->dispatchBatchSize]);
        $this->assertSame([5.0, 9.0], $own->dispatchRadiusStepsKm);
    }

    public function test_the_default_usage_repository_reports_zero_without_a_bookings_table(): void
    {
        $repository = new \App\Repositories\Eloquent\Catalog\CatalogUsageRepository;
        $category = ServiceCategory::factory()->create(['service_family_id' => ServiceFamily::factory()]);

        $this->assertSame(0, $repository->activeBookingCount($category));
    }

    public function test_publishing_uses_a_dedicated_schema_row_per_version(): void
    {
        $category = $this->category();
        app(ServiceCategoryPublisher::class)->publish($category, 'First release', null);

        $this->assertSame(1, ServiceFormSchema::query()->where('status', ContentStatus::Draft)->count());
        $this->assertSame(1, ServiceFormSchema::query()->where('status', ContentStatus::Published)->count());
    }
}
