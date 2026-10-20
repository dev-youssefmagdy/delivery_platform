<?php

namespace Tests\Feature\Provider;

use App\Enums\Provider\ProviderStatus;
use App\Events\ProviderStatusChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ProviderStatusEvent;
use App\Services\Provider\ProviderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * Plan §7.3: the provider status graph, its guards and its history.
 */
class ProviderStatusMachineTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    public function test_the_graph_matches_the_plan(): void
    {
        $this->assertSame([ProviderStatus::PhoneVerified], ProviderStatus::Draft->allowedTransitions());
        $this->assertTrue(ProviderStatus::UnderReview->canTransitionTo(ProviderStatus::Approved));
        $this->assertTrue(ProviderStatus::UnderReview->canTransitionTo(ProviderStatus::MoreInformationRequired));
        $this->assertTrue(ProviderStatus::MoreInformationRequired->canTransitionTo(ProviderStatus::UnderReview));
        $this->assertTrue(ProviderStatus::Rejected->canTransitionTo(ProviderStatus::UnderReview), 'rejected is resubmittable');
        $this->assertTrue(ProviderStatus::Approved->canTransitionTo(ProviderStatus::TemporarilySuspended));
        $this->assertTrue(ProviderStatus::TemporarilySuspended->canTransitionTo(ProviderStatus::Approved));
        $this->assertFalse(ProviderStatus::PermanentlySuspended->canTransitionTo(ProviderStatus::Approved), 'a permanent suspension cannot be lifted');
        $this->assertFalse(ProviderStatus::Approved->canTransitionTo(ProviderStatus::UnderReview));
        $this->assertFalse(ProviderStatus::DocumentsPending->canTransitionTo(ProviderStatus::Approved), 'approval needs a review');
        $this->assertFalse(ProviderStatus::Draft->canTransitionTo(ProviderStatus::Approved));
        $this->assertNotContains('online_eligible', ProviderStatus::values(), 'online_eligible is computed, never a status');
        $this->assertNotContains('expired_documents', ProviderStatus::values());
    }

    public function test_every_other_status_can_reach_deletion_pending_except_draft(): void
    {
        foreach (ProviderStatus::cases() as $status) {
            if (! in_array($status, [ProviderStatus::Draft, ProviderStatus::DeletionPending], true)) {
                $this->assertTrue($status->canTransitionTo(ProviderStatus::DeletionPending), $status->value);
            }
        }
    }

    public function test_the_service_writes_history_dispatches_the_event_and_refuses_invalid_moves(): void
    {
        Event::fake([ProviderStatusChanged::class]);
        $provider = $this->makeProvider(ProviderStatus::UnderReview);
        $admin = Admin::factory()->create();
        $service = app(ProviderStatusService::class);

        $service->transition($provider, ProviderStatus::Approved, $admin, 'All good');

        $provider->refresh();
        $this->assertSame(ProviderStatus::Approved, $provider->status);
        $this->assertNotNull($provider->approved_at);
        $event = ProviderStatusEvent::query()->sole();
        $this->assertSame([ProviderStatus::UnderReview, ProviderStatus::Approved, 'admin', $admin->id, 'All good'], [$event->from_status, $event->to_status, $event->actor_type, $event->actor_id, $event->reason]);
        Event::assertDispatched(ProviderStatusChanged::class, fn (ProviderStatusChanged $e): bool => $e->to === ProviderStatus::Approved && $e->from === ProviderStatus::UnderReview);

        try {
            $service->transition($provider, ProviderStatus::DocumentsPending, $admin);
            $this->fail('An invalid transition must be refused.');
        } catch (BusinessRuleViolation $e) {
            $this->assertSame('provider_status_transition_invalid', $e->errorCode);
        }

        $this->assertSame(ProviderStatus::Approved, $provider->refresh()->status);
        $this->assertSame(1, ProviderStatusEvent::query()->count());
    }

    public function test_a_suspension_takes_the_provider_offline(): void
    {
        $provider = $this->makeProvider(ProviderStatus::Approved, ['is_online' => true, 'online_since' => now()]);

        app(ProviderStatusService::class)->transition($provider, ProviderStatus::TemporarilySuspended, null, 'Complaint', now()->addDay());

        $provider->refresh();
        $this->assertFalse($provider->is_online);
        $this->assertNull($provider->online_since);
        $this->assertNotNull($provider->status_expires_at);
        $this->assertSame('system', ProviderStatusEvent::query()->sole()->actor_type);
    }

    public function test_history_rows_are_append_only(): void
    {
        $provider = $this->makeProvider(ProviderStatus::UnderReview);
        app(ProviderStatusService::class)->transition($provider, ProviderStatus::Approved, null);

        $this->expectException(LogicException::class);
        ProviderStatusEvent::query()->firstOrFail()->update(['reason' => 'edited']);
    }
}
