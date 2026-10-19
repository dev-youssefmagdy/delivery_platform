<?php

namespace Tests\Feature\Admin\Policy;

use App\DataTables\Admin\Policy\PolicyVersionsDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Policy versions, plus the publish lifecycle.
 */
class PolicyVersionAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    private const MANAGE = [AdminPermission::PoliciesManage];

    private const PUBLISH = [AdminPermission::PoliciesManage, AdminPermission::PoliciesPublish];

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.policy-versions.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $policy = PolicyVersion::factory()->create();

        $this->get(route('admin.policy-versions.index'))->assertForbidden();
        $this->getJson(route('admin.policy-versions.show', $policy))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.policy-versions.index'))->assertForbidden();
        $this->postJson(route('admin.policy-versions.store'), $this->payload())->assertForbidden();
        $this->postJson(route('admin.policy-versions.publish', $policy), ['reason' => 'ready to go'])->assertForbidden();
    }

    public function test_index_renders_the_listing_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin(self::MANAGE);

        $this->get(route('admin.policy-versions.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.PolicyVersionsDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.PolicyVersionsDataTable::TABLE_ID.'"', false);
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin(self::MANAGE);
        $privacy = PolicyVersion::factory()->published(1)->create(['type' => PolicyType::Privacy, 'title' => ['ar' => 'الخصوصية', 'en' => 'Needle privacy']]);
        PolicyVersion::factory()->create();

        $url = route('admin.policy-versions.index');
        $this->datatable($url)->assertJsonPath('recordsTotal', 2);
        $this->datatable($url, ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $privacy->id);
        $this->datatable($url, ['types' => ['privacy']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['statuses' => ['draft']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['statuses' => ['published', 'archived']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['published_at' => ['from' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin(self::MANAGE);

        $this->precognitive('POST', route('admin.policy-versions.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'title.ar', 'content.ar']);
        $this->precognitive('POST', route('admin.policy-versions.store'), $this->payload())->assertNoContent();

        $this->assertDatabaseCount('policy_versions', 0);
    }

    public function test_store_creates_a_numberless_draft_and_audits_it(): void
    {
        $this->actingAsAdmin(self::MANAGE);

        $this->postJson(route('admin.policy-versions.store'), $this->payload())->assertOk();

        $policy = PolicyVersion::query()->sole();
        $this->assertSame(ContentStatus::Draft, $policy->status);
        $this->assertNull($policy->version);
        $this->assertNotEmpty($policy->uuid);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'policy_version', 'auditable_id' => (string) $policy->id]);
    }

    public function test_drafts_can_be_edited_with_optimistic_locking_and_deleted(): void
    {
        $this->actingAsAdmin(self::MANAGE);
        $policy = PolicyVersion::factory()->create();

        $this->putJson(route('admin.policy-versions.update', $policy), $this->payload(['title' => ['ar' => 'عنوان جديد', 'en' => 'New title']]) + ['lock_version' => 0])->assertOk();
        $this->assertSame('New title', $policy->refresh()->getTranslation('title', 'en'));
        $this->assertSame(1, $policy->lock_version);

        $this->putJson(route('admin.policy-versions.update', $policy), $this->payload() + ['lock_version' => 0])->assertStatus(409);

        $this->deleteJson(route('admin.policy-versions.destroy', $policy))->assertOk();
        $this->assertModelMissing($policy);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $policy->id]);
    }

    public function test_publish_requires_the_publish_permission_and_a_reason(): void
    {
        $policy = PolicyVersion::factory()->create();
        $this->actingAsAdmin(self::MANAGE);
        $this->postJson(route('admin.policy-versions.publish', $policy), ['reason' => 'ready to go'])->assertForbidden();

        $this->actingAsAdmin(self::PUBLISH);
        $this->postJson(route('admin.policy-versions.publish', $policy), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->precognitive('POST', route('admin.policy-versions.publish', $policy), ['reason' => 'ok'])->assertStatus(422);
        $this->assertTrue($policy->refresh()->isDraft());
    }

    public function test_publishing_numbers_the_version_archives_the_previous_one_and_freezes_the_row(): void
    {
        $admin = $this->actingAsAdmin(self::PUBLISH);
        $first = PolicyVersion::factory()->create();

        $this->postJson(route('admin.policy-versions.publish', $first), ['reason' => 'initial release'])->assertOk();
        $first->refresh();
        $this->assertSame(ContentStatus::Published, $first->status);
        $this->assertSame(1, $first->version);
        $this->assertNotNull($first->published_at);
        $this->assertSame($admin->id, $first->published_by_admin_id);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Published->value, 'auditable_id' => (string) $first->id, 'reason' => 'initial release']);

        // Published rows are immutable.
        $this->putJson(route('admin.policy-versions.update', $first), $this->payload() + ['lock_version' => $first->lock_version])->assertForbidden();
        $this->deleteJson(route('admin.policy-versions.destroy', $first))->assertForbidden();
        $this->postJson(route('admin.policy-versions.publish', $first), ['reason' => 'again please'])->assertForbidden();

        $second = PolicyVersion::factory()->create();
        $other = PolicyVersion::factory()->create(['type' => PolicyType::Privacy]);
        $this->postJson(route('admin.policy-versions.publish', $second), ['reason' => 'new wording'])->assertOk();
        $this->postJson(route('admin.policy-versions.publish', $other), ['reason' => 'privacy release'])->assertOk();

        $this->assertSame(2, $second->refresh()->version);
        $this->assertSame(ContentStatus::Archived, $first->refresh()->status);
        $this->assertSame(1, $other->refresh()->version, 'Numbering is per type.');
        $this->assertSame(ContentStatus::Published, $other->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $first->id]);
    }

    public function test_a_draft_without_arabic_text_cannot_be_published(): void
    {
        $this->actingAsAdmin(self::PUBLISH);
        $policy = PolicyVersion::factory()->create(['title' => ['en' => 'Only English'], 'content' => ['en' => 'Only English']]);

        $this->postJson(route('admin.policy-versions.publish', $policy), ['reason' => 'ready to go'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'policy_arabic_required');
        $this->assertTrue($policy->refresh()->isDraft());
    }

    public function test_show_create_and_edit_pages_render(): void
    {
        $this->actingAsAdmin(self::PUBLISH);
        $policy = PolicyVersion::factory()->create();

        $this->get(route('admin.policy-versions.show', $policy))->assertOk()->assertSee(route('admin.policy-versions.publish', ['policyVersion' => $policy, 'from_show' => 1]), false);
        $this->get(route('admin.policy-versions.create'))->assertOk()->assertSee('name="content[ar]"', false);
        $this->get(route('admin.policy-versions.edit', $policy))->assertOk()->assertSee('name="lock_version"', false);
        $this->get(route('admin.policy-versions.activity', $policy))->assertOk();

        $published = PolicyVersion::factory()->published()->create(['type' => PolicyType::Privacy]);
        $this->get(route('admin.policy-versions.show', $published))->assertOk()->assertDontSee(route('admin.policy-versions.publish', ['policyVersion' => $published, 'from_show' => 1]), false);
        $this->get(route('admin.policy-versions.edit', $published))->assertForbidden();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return $override + [
            'type' => 'terms',
            'title' => ['ar' => 'الشروط والأحكام', 'en' => 'Terms and conditions'],
            'content' => ['ar' => 'نص الشروط', 'en' => 'Terms text'],
            'change_note' => 'First draft',
        ];
    }
}
