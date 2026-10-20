<?php

namespace Tests\Feature\Provider;

use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\EligibilityReason;
use App\Models\AdminAuditLog;
use App\Services\Provider\ProviderEligibilityService;
use App\Services\Provider\ProviderNotifier;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Concerns\BuildsProviders;
use Tests\TestCase;

/**
 * `documents:check-expiry` (daily): expired documents are marked and audited, reminders go out once per threshold,
 * and a provider who stops being eligible is taken offline.
 */
class DocumentExpiryTest extends TestCase
{
    use BuildsProviders;
    use RefreshDatabase;

    public function test_the_command_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())->first(static fn ($e): bool => str_contains((string) $e->command, 'documents:check-expiry'));

        $this->assertNotNull($event);
        $this->assertSame('0 2 * * *', $event->expression);
    }

    public function test_expired_documents_are_marked_audited_and_the_provider_goes_offline(): void
    {
        ['provider' => $provider, 'vehicle' => $vehicle] = $this->eligibleProvider();
        $provider->forceFill(['is_online' => true, 'online_since' => now()])->save();
        $licence = $this->providerDocument($provider, $this->documentType(['code' => 'licence', 'requires_expiry' => true], ['provider_type' => $provider->provider_type]), DocumentStatus::Approved, now()->subDay()->toDateString());
        $registration = $this->documentOfVehicle($vehicle, $this->vehicleDocumentType(['code' => 'registration', 'requires_expiry' => true]), DocumentStatus::Approved, now()->subDays(3)->toDateString());
        $fine = $this->providerDocument($provider, $this->documentType(['code' => 'permit', 'requires_expiry' => true], null), DocumentStatus::Approved, now()->addMonths(6)->toDateString());
        $notifier = Mockery::spy(ProviderNotifier::class);
        $this->app->instance(ProviderNotifier::class, $notifier);

        $this->artisan('documents:check-expiry')->expectsOutputToContain('Expired: 2')->assertSuccessful();

        $this->assertSame(DocumentStatus::Expired, $licence->refresh()->status);
        $this->assertSame(DocumentStatus::Expired, $registration->refresh()->status);
        $this->assertSame(DocumentStatus::Approved, $fine->refresh()->status);
        $this->assertFalse($provider->refresh()->is_online, 'an ineligible provider is taken offline');
        $this->assertTrue(app(ProviderEligibilityService::class)->check($provider)->has(EligibilityReason::DocumentExpired));

        $logs = AdminAuditLog::query()->where('action', AuditAction::StatusChanged->value)->get();
        $this->assertCount(2, $logs);
        $this->assertSame('system', $logs[0]->actor_type);
        $this->assertSame('licence', $logs[0]->meta['document_type']);
        $notifier->shouldHaveReceived('documentExpired')->twice();

        $this->artisan('documents:check-expiry')->expectsOutputToContain('Expired: 0')->assertSuccessful();
        $this->assertSame(2, AdminAuditLog::query()->where('action', AuditAction::StatusChanged->value)->count(), 'idempotent');
    }

    public function test_reminders_are_sent_once_per_threshold(): void
    {
        $provider = $this->makeProvider();
        $type = $this->documentType(['code' => 'licence', 'requires_expiry' => true], null);
        $far = $this->providerDocument($provider, $type, DocumentStatus::Approved, now()->addDays(25)->toDateString());
        $soon = $this->providerDocument($provider, $this->documentType(['code' => 'permit', 'requires_expiry' => true], null), DocumentStatus::Approved, now()->addDays(5)->toDateString());
        $none = $this->providerDocument($provider, $this->documentType(['code' => 'insurance', 'requires_expiry' => true], null), DocumentStatus::Approved, now()->addDays(90)->toDateString());
        $notifier = Mockery::spy(ProviderNotifier::class);
        $this->app->instance(ProviderNotifier::class, $notifier);

        $this->artisan('documents:check-expiry')->assertSuccessful();
        $notifier->shouldHaveReceived('documentExpiring')->twice();
        $this->assertSame([30, 7, null], [$far->refresh()->expiry_reminder_days, $soon->refresh()->expiry_reminder_days, $none->refresh()->expiry_reminder_days]);

        $this->artisan('documents:check-expiry')->assertSuccessful();
        $notifier->shouldHaveReceived('documentExpiring')->twice();

        $this->travel(19)->days();
        $this->artisan('documents:check-expiry')->assertSuccessful();
        $notifier->shouldHaveReceived('documentExpiring')->times(3);
        $this->assertSame(7, $far->refresh()->expiry_reminder_days);
    }

    public function test_the_notification_hook_only_logs_and_says_nothing_was_sent(): void
    {
        Log::spy();
        $provider = $this->makeProvider();
        $document = $this->providerDocument($provider, $this->documentType(['code' => 'licence', 'requires_expiry' => true], null), DocumentStatus::Approved, now()->addDays(3)->toDateString());

        app(ProviderNotifier::class)->documentExpiring($provider, $document, 3);

        Log::shouldHaveReceived('info')->withArgs(fn (string $message): bool => str_contains($message, 'notification stub, nothing sent'))->once();
    }

    public function test_documents_without_an_expiry_date_never_expire(): void
    {
        $provider = $this->makeProvider();
        $document = $this->providerDocument($provider, $this->documentType(['code' => 'id_card'], null), DocumentStatus::Approved);

        $this->artisan('documents:check-expiry')->assertSuccessful();

        $this->assertSame(DocumentStatus::Approved, $document->refresh()->status);
    }
}
