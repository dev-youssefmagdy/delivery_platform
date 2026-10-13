<?php

namespace Tests\Feature\Support;

use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Models\AdminAuditLog;
use App\Models\Currency;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use LogicException;
use Tests\TestCase;

/**
 * Append-only audit foundation (Plan §4.4, SRS §23.4).
 */
class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_model_action_with_actor_snapshots_and_request_context(): void
    {
        Context::add('request_id', 'req-abc-123');
        Context::addHidden('ip', '203.0.113.9');
        Context::addHidden('user_agent', 'PHPUnit');

        $actor = User::factory()->create();
        $currency = Currency::factory()->create(['code' => 'USD']);

        $log = app(AuditLogger::class)->record(
            AuditAction::Created,
            $currency,
            actor: $actor,
            after: $currency->auditSnapshot(),
            reason: 'initial setup',
            meta: ['source' => 'test'],
        );

        $log->refresh();

        $this->assertSame('user', $log->actor_type);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame(AuditAction::Created, $log->action);
        $this->assertSame('currency', $log->auditable_type);
        $this->assertSame((string) $currency->id, $log->auditable_id);
        $this->assertSame('USD', $log->after['code'] ?? null);
        $this->assertNull($log->before);
        $this->assertSame('initial setup', $log->reason);
        $this->assertSame('203.0.113.9', $log->ip);
        $this->assertSame('PHPUnit', $log->user_agent);
        $this->assertSame('req-abc-123', $log->request_id);
        $this->assertSame(['source' => 'test'], $log->meta);
    }

    public function test_system_actor_and_string_targets(): void
    {
        $log = app(AuditLogger::class)->record(AuditAction::Updated, 'settings');

        $this->assertSame(ActorType::System->value, $log->actor_type);
        $this->assertNull($log->actor_id);
        $this->assertSame('settings', $log->auditable_type);
        $this->assertNull($log->auditable_id);
    }

    public function test_record_changes_keeps_only_changed_attributes(): void
    {
        $currency = Currency::factory()->create(['code' => 'USD', 'symbol_en' => '$', 'active' => true]);
        $before = $currency->auditSnapshot();
        $currency->update(['active' => false]);

        $log = app(AuditLogger::class)->recordChanges(AuditAction::Updated, $currency, null, $before, $currency->auditSnapshot());

        $this->assertSame(['active' => true], array_diff_key($log->before ?? [], ['updated_at' => true]));
        $this->assertSame(['active' => false], array_diff_key($log->after ?? [], ['updated_at' => true]));
    }

    public function test_audit_snapshot_excludes_hidden_and_explicitly_excluded_attributes(): void
    {
        $model = new class extends Currency
        {
            protected $table = 'currencies';

            protected $hidden = ['symbol_ar'];

            /** @var list<string> */
            protected array $auditExcluded = ['exponent'];
        };
        $model->forceFill(['code' => 'USD', 'exponent' => 2, 'symbol_ar' => '$', 'symbol_en' => '$', 'active' => true]);

        $snapshot = $model->auditSnapshot();

        $this->assertSame('USD', $snapshot['code']);
        $this->assertArrayNotHasKey('symbol_ar', $snapshot);
        $this->assertArrayNotHasKey('exponent', $snapshot);
    }

    public function test_entries_are_append_only(): void
    {
        $log = app(AuditLogger::class)->record(AuditAction::Viewed, 'reports');

        try {
            $log->update(['reason' => 'tampered']);
            $this->fail('Audit entries must not be updatable.');
        } catch (LogicException) {
            // expected
        }

        try {
            $log->delete();
            $this->fail('Audit entries must not be deletable.');
        } catch (LogicException) {
            // expected
        }

        $this->assertNull(AdminAuditLog::query()->findOrFail($log->id)->reason);
    }
}
