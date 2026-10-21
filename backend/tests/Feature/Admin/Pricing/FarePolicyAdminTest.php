<?php

namespace Tests\Feature\Admin\Pricing;

use App\DataTables\Admin\Pricing\FarePoliciesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookings;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Fare policies.
 */
class FarePolicyAdminTest extends TestCase
{
    use BuildsBookings;
    use InteractsWithAdmin;
    use RefreshDatabase;

    private ServiceCategory $category;

    private ServiceZone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBookingReferenceData();
        $this->category = $this->rideCategory();
        $this->zone = $this->zoneOffering([$this->category]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'service_category_id' => $this->category->id, 'service_zone_id' => '', 'formula_version' => 1,
            'base_minor' => 1000, 'per_km_minor' => 400, 'per_min_minor' => 50, 'stop_fee_minor' => 500, 'min_fare_minor' => 2000,
            'rounding_step_minor' => 100, 'range_low_pct' => '85', 'range_high_pct' => '125',
            'effective_from' => now()->subDay()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i'), 'effective_to' => '',
            'notes' => 'Starter prices',
            'surcharges' => [['field' => 'child_seat', 'mode' => 'fixed', 'amount' => '15.50', 'match' => '', 'label_ar' => 'مقعد أطفال', 'label_en' => 'Child seat']],
        ];
    }

    private function update(FarePolicy $policy, array $overrides = []): array
    {
        $payload = $this->payload($overrides);
        unset($payload['service_category_id'], $payload['service_zone_id']);

        return $payload + ['lock_version' => $policy->lock_version];
    }

    public function test_guests_are_blocked_and_other_permissions_are_not_enough(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.fare-policies.index')));
        $policy = FarePolicy::factory()->create(['service_category_id' => $this->category->id]);

        $this->actingAsAdmin([AdminPermission::ServicesManage, AdminPermission::BookingsView, AdminPermission::SettingsView]);

        $this->get(route('admin.fare-policies.index'))->assertForbidden();
        $this->get(route('admin.fare-policies.create'))->assertForbidden();
        $this->getJson(route('admin.fare-policies.show', $policy))->assertForbidden();
        $this->datatable(route('admin.fare-policies.index'))->assertForbidden();
        $this->postJson(route('admin.fare-policies.store'), $this->payload())->assertForbidden();
        $this->putJson(route('admin.fare-policies.update', $policy), $this->update($policy))->assertForbidden();
        $this->postJson(route('admin.fare-policies.deactivate', $policy), ['reason' => 'Because reasons'])->assertForbidden();
        $this->postJson(route('admin.fare-policies.preview', $policy), ['distance_km' => 5, 'duration_min' => 10])->assertForbidden();
        $this->deleteJson(route('admin.fare-policies.destroy', $policy), ['reason' => 'Because reasons'])->assertForbidden();
    }

    public function test_the_menu_shows_the_module_under_pricing(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);

        $this->get(route('admin.fare-policies.index'))->assertOk()->assertSee(route('admin.fare-policies.index'), false)->assertSee(__('admin.fare_policies.menu'));
    }

    public function test_index_has_a_collapsed_filters_card_and_every_filter_works(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $other = $this->rideCategory();
        $match = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'service_zone_id' => $this->zone->id, 'effective_from' => '2026-01-01 00:00:00', 'effective_to' => '2026-03-01 00:00:00']);
        $off = FarePolicy::factory()->inactive()->create(['service_category_id' => $other->id, 'effective_from' => '2026-06-01 00:00:00']);

        $this->get(route('admin.fare-policies.index'))->assertOk()
            ->assertSee('data-filters-for="'.FarePoliciesDataTable::TABLE_ID.'"', false)
            ->assertSee('x-data="filtersCard(', false)
            ->assertSee('id="'.FarePoliciesDataTable::TABLE_ID.'"', false);

        $url = route('admin.fare-policies.index');
        $this->datatable($url)->assertJsonPath('recordsTotal', 2);
        $this->assertSame([$match->id], array_column($this->datatable($url, ['category_ids' => [$this->category->id]])->json('data'), 'id'));
        $this->assertSame([$off->id], array_column($this->datatable($url, ['category_ids' => [$other->id]])->json('data'), 'id'));
        $this->assertSame([$match->id], array_column($this->datatable($url, ['zone_ids' => [$this->zone->id]])->json('data'), 'id'));
        $this->assertSame([$off->id], array_column($this->datatable($url, ['active' => '0'])->json('data'), 'id'));
        $this->assertSame([$match->id], array_column($this->datatable($url, ['active' => '1'])->json('data'), 'id'));
        $this->assertSame([$match->id], array_column($this->datatable($url, ['effective_on' => ['from' => '2026-02-01', 'to' => '2026-02-15']])->json('data'), 'id'));
        $this->assertSame([$off->id], array_column($this->datatable($url, ['effective_on' => ['from' => '2026-07-01', 'to' => '2026-07-10']])->json('data'), 'id'));
        $this->datatable($url, ['effective_on' => ['from' => '2025-01-01', 'to' => '2025-12-31']])->assertJsonPath('recordsFiltered', 0);

        $row = $this->datatable($url, ['category_ids' => [$this->category->id]])->json('data.0');
        $this->assertStringContainsString($this->category->name, $row['service']);
        $this->assertStringContainsString('actions', json_encode(array_keys($row)));
    }

    public function test_the_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $policy = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'attribute_surcharges' => [['field' => 'helpers', 'mode' => 'per_unit', 'amount_minor' => 2500, 'match' => null, 'label' => ['ar' => 'عامل', 'en' => 'Helper']]]]);

        $this->get(route('admin.fare-policies.create'))->assertOk()->assertSee('data-ajax-form', false)->assertSee('name="base_minor"', false)->assertSee('name="range_low_pct"', false);
        $this->get(route('admin.fare-policies.edit', $policy))->assertOk()->assertSee('name="lock_version"', false)->assertSee('value="helpers"', false)->assertSee('value="25.00"', false);
        $this->get(route('admin.fare-policies.show', $policy))->assertOk()->assertSee(__('admin.fare_policies.no_surge_notice'))->assertSee(route('admin.fare-policies.preview', $policy), false);
        $this->get(route('admin.fare-policies.activity', $policy))->assertOk();
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $url = route('admin.fare-policies.store');

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['service_category_id', 'formula_version', 'base_minor', 'per_km_minor', 'min_fare_minor', 'effective_from', 'range_low_pct']);
        $this->precognitive('POST', $url, $this->payload(['base_minor' => -5, 'range_low_pct' => '150', 'range_high_pct' => '50', 'effective_to' => '2000-01-01 00:00', 'service_zone_id' => 99999, 'formula_version' => 9]))
            ->assertStatus(422)->assertJsonValidationErrors(['base_minor', 'range_low_pct', 'range_high_pct', 'effective_to', 'service_zone_id', 'formula_version']);
        $this->precognitive('POST', $url, $this->payload(['surcharges' => [['field' => 'Bad Key', 'mode' => 'sideways', 'amount' => '1.234567']]]))
            ->assertStatus(422)->assertJsonValidationErrors(['surcharges.0.field', 'surcharges.0.mode', 'surcharges.0.amount']);
        $this->precognitive('POST', $url, $this->payload())->assertNoContent();
        $this->assertDatabaseCount('fare_policies', 0);
    }

    public function test_store_creates_audits_and_stores_minor_units(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);

        $this->postJson(route('admin.fare-policies.store'), $this->payload(['service_zone_id' => (string) $this->zone->id]))->assertOk()->assertJsonPath('success', true);

        $policy = FarePolicy::query()->firstOrFail();
        $this->assertSame([$this->category->id, $this->zone->id, 'EGP'], [$policy->service_category_id, $policy->service_zone_id, $policy->currency]);
        $this->assertSame([1000, 400, 50, 500, 2000, 100], [$policy->base_minor, $policy->per_km_minor, $policy->per_min_minor, $policy->stop_fee_minor, $policy->min_fare_minor, $policy->rounding_step_minor]);
        $this->assertSame([8500, 12500], [$policy->range_low_bp, $policy->range_high_bp]);
        $this->assertTrue($policy->active);
        $this->assertSame(1550, $policy->attribute_surcharges[0]['amount_minor']);
        $this->assertSame('child_seat', $policy->attribute_surcharges[0]['field']);
        $this->assertNull($policy->effective_to);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'fare_policy', 'auditable_id' => (string) $policy->id, 'actor_id' => $admin->id]);
    }

    public function test_update_uses_optimistic_locking_audits_and_keeps_the_scope(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $policy = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'service_zone_id' => $this->zone->id]);
        $body = $this->update($policy, ['base_minor' => 1500, 'service_category_id' => 99999]);

        $this->precognitive('PUT', route('admin.fare-policies.update', $policy), array_merge($body, ['base_minor' => 'x']))->assertStatus(422)->assertJsonValidationErrors(['base_minor']);
        $this->putJson(route('admin.fare-policies.update', $policy), $body)->assertOk();

        $policy->refresh();
        $this->assertSame(1500, $policy->base_minor);
        $this->assertSame($this->category->id, $policy->service_category_id, 'scope cannot change');
        $this->assertSame($this->zone->id, $policy->service_zone_id);
        $this->assertSame(1, $policy->lock_version);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_type' => 'fare_policy']);

        $this->putJson(route('admin.fare-policies.update', $policy), $body)->assertStatus(409);
        $this->assertSame(1500, $policy->fresh()->base_minor);
    }

    public function test_enabled_policies_of_the_same_scope_may_not_overlap(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $first = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'effective_from' => now()->subDays(10), 'effective_to' => now()->addDays(10)]);

        $this->postJson(route('admin.fare-policies.store'), $this->payload(['effective_from' => now()->addDays(5)->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')]))
            ->assertStatus(422)->assertJsonPath('errors._code', 'fare_policy_overlap');
        $this->assertDatabaseCount('fare_policies', 1);

        // After the first one ends, a new one is fine; so is another scope (zone) or a disabled one.
        $this->postJson(route('admin.fare-policies.store'), $this->payload(['effective_from' => now()->addDays(11)->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')]))->assertOk();
        $this->postJson(route('admin.fare-policies.store'), $this->payload(['service_zone_id' => (string) $this->zone->id, 'effective_from' => now()->addDays(5)->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')]))->assertOk();
        $this->assertDatabaseCount('fare_policies', 3);

        // Enabling a disabled one that would overlap is refused; editing the period of an enabled one is checked too.
        $this->postJson(route('admin.fare-policies.deactivate', $first), ['reason' => 'Replacing it'])->assertOk();
        $second = FarePolicy::query()->where('service_zone_id', null)->where('id', '!=', $first->id)->firstOrFail();
        $this->postJson(route('admin.fare-policies.store'), $this->payload(['effective_from' => now()->addDays(5)->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i'), 'effective_to' => now()->addDays(9)->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')]))->assertOk();
        $this->postJson(route('admin.fare-policies.activate', $first))->assertStatus(422)->assertJsonPath('errors._code', 'fare_policy_overlap');
        $this->assertFalse($first->fresh()->active);
        $this->putJson(route('admin.fare-policies.update', $second), $this->update($second, ['effective_from' => now()->addDays(6)->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i')]))->assertStatus(422)->assertJsonPath('errors._code', 'fare_policy_overlap');
        $this->assertNotNull($admin);
    }

    public function test_the_period_and_range_rules_are_enforced_by_the_service_too(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $service = app(\App\Services\Pricing\FarePolicyService::class);
        $data = static fn (array $over = []): \App\DTOs\Pricing\FarePolicyData => new \App\DTOs\Pricing\FarePolicyData(...($over + [
            'serviceCategoryId' => ServiceCategory::query()->firstOrFail()->id, 'serviceZoneId' => null, 'formula' => \App\Enums\Pricing\FareFormula::V1, 'currency' => 'EGP',
            'baseMinor' => 1, 'perKmMinor' => 1, 'perMinMinor' => 1, 'minFareMinor' => 1, 'stopFeeMinor' => 1, 'surcharges' => [], 'roundingStepMinor' => 100,
            'rangeLowBp' => 8500, 'rangeHighBp' => 12500, 'effectiveFrom' => \Carbon\CarbonImmutable::now(), 'effectiveTo' => null, 'notes' => null,
        ]));

        foreach ([
            ['effectiveTo' => \Carbon\CarbonImmutable::now()->subDay()], ['rangeLowBp' => 10_001], ['rangeLowBp' => 0], ['rangeHighBp' => 9999], ['serviceCategoryId' => 987_654],
            ['serviceZoneId' => 987_654], ['surcharges' => [['field' => 'Bad', 'mode' => 'fixed', 'amount_minor' => 100, 'match' => null, 'label' => []]]],
            ['surcharges' => [['field' => 'ok', 'mode' => 'fixed', 'amount_minor' => -1, 'match' => null, 'label' => []]]],
        ] as $bad) {
            try {
                $service->create($data($bad), null);
                $this->fail('expected a business rule violation for '.json_encode(array_keys($bad)));
            } catch (\App\Exceptions\BusinessRuleViolation) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('fare_policies', 0);
    }

    public function test_activate_deactivate_and_delete_follow_the_contract(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $policy = FarePolicy::factory()->create(['service_category_id' => $this->category->id]);

        $this->postJson(route('admin.fare-policies.deactivate', $policy), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->precognitive('POST', route('admin.fare-policies.deactivate', $policy), ['reason' => 'x'])->assertStatus(422);
        $this->postJson(route('admin.fare-policies.deactivate', $policy), ['reason' => 'Prices are being renegotiated'])->assertOk();
        $this->assertFalse($policy->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'reason' => 'Prices are being renegotiated', 'auditable_type' => 'fare_policy']);
        $this->postJson(route('admin.fare-policies.deactivate', $policy), ['reason' => 'Prices are being renegotiated'])->assertForbidden();

        $this->postJson(route('admin.fare-policies.activate', $policy))->assertOk();
        $this->assertTrue($policy->refresh()->active);
        $this->postJson(route('admin.fare-policies.activate', $policy))->assertForbidden();

        $this->deleteJson(route('admin.fare-policies.destroy', $policy), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->deleteJson(route('admin.fare-policies.destroy', $policy), ['reason' => 'Created by mistake'])->assertOk();
        $this->assertModelMissing($policy);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'reason' => 'Created by mistake', 'auditable_type' => 'fare_policy']);
    }

    public function test_row_actions_are_permission_aware_and_dangerous_ones_ask_for_a_reason(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        FarePolicy::factory()->create(['service_category_id' => $this->category->id]);

        $html = (string) json_encode($this->datatable(route('admin.fare-policies.index'))->json('data.0.actions'));

        $this->assertStringContainsString('deactivate', $html);
        $this->assertStringContainsString('reason', strtolower($html));
    }

    public function test_the_calculator_previews_without_saving_anything(): void
    {
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);
        $policy = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'attribute_surcharges' => [['field' => 'helpers', 'mode' => 'per_unit', 'amount_minor' => 2500, 'match' => null]]]);
        $url = route('admin.fare-policies.preview', $policy);

        $this->precognitive('POST', $url, ['distance_km' => 'far', 'duration_min' => -1])->assertStatus(422)->assertJsonValidationErrors(['distance_km', 'duration_min']);

        $html = $this->postJson($url, ['distance_km' => 10, 'duration_min' => 20, 'stops' => 0, 'answers' => [['key' => 'helpers', 'value' => '2']]])
            ->assertOk()->json('data.replace_html.#fare-preview-result');

        // 1000 + 400 x 10 + 50 x 20 + 2 x 2500 = 11000 -> 110.00
        $this->assertStringContainsString('110', (string) preg_replace('/[^0-9.]/u', '', strtr($html, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])));
        $this->assertDatabaseCount('fare_policies', 1);
        $this->assertSame(0, $policy->fresh()->lock_version);
    }
}
