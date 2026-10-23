<?php

namespace App\Services\Safety;

use App\DTOs\Safety\LocationFix;
use App\DTOs\Safety\ReportIncidentData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Identity\ClientApp;
use App\Enums\Safety\IncidentCategory;
use App\Enums\Safety\IncidentEventType;
use App\Enums\Safety\IncidentPriority;
use App\Enums\Safety\IncidentSeverity;
use App\Enums\Safety\IncidentStatus;
use App\Enums\Safety\IncidentTrigger;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\RateLimitedException;
use App\Models\Booking;
use App\Models\SafetyIncident;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Safety\IncidentThreadRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Services\Booking\BookingReportService;
use App\Support\Auth\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Raises a safety case from an app (SRS §13.2): SOS on an active job or an incident report. An SOS captures the booking, the
 * time, the actor, the GPS fix (or the last known one), the callback number and the category; it is NEVER refused, never counted
 * against the report limit, and a repeated press joins the still-open case. A non-urgent report is limited per hour.
 * Afterwards the safety team and, per consent, the trusted contacts are told; the result lists exactly what was done and that
 * emergency services were NOT contacted by the platform.
 */
final class IncidentReportingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly SafetyIncidentRepositoryInterface $incidents,
        private readonly IncidentThreadRepositoryInterface $thread,
        private readonly TrustedContactRepositoryInterface $contacts,
        private readonly ProviderLastLocationRepositoryInterface $locations,
        private readonly SafetyNotifier $notifier,
        private readonly TrustedContactNotifier $contactNotifier,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `sos_not_available`, `report_not_available`
     * @throws RateLimitedException `safety_report_rate_limited` (reports only)
     */
    public function report(User $user, ClientApp $app, ReportIncidentData $data): ReportedIncident
    {
        $booking = $this->resolveBooking($user, $app, $data->bookingUuid);
        $isSos = $data->trigger === IncidentTrigger::Sos;

        if ($isSos ? ! $this->sosAllowed($booking) : ! BookingReportService::canReport($booking)) {
            throw BusinessRuleViolation::make($isSos ? 'sos_not_available' : 'report_not_available');
        }

        if ($isSos) {
            $existing = $this->incidents->activeSosFor($booking->id, $user->id);

            if ($existing instanceof SafetyIncident) {
                return $this->join($existing, $user, $app, $data);
            }
        } else {
            $limit = (int) config('safety.reports.per_hour', 6);

            if ($this->incidents->countReportsSince($user->id, CarbonImmutable::now()->subHour()) >= $limit) {
                throw RateLimitedException::afterSeconds('safety_report_rate_limited', 600);
            }
        }

        $category = $data->category;
        $severity = $category->defaultSeverity();

        if ($isSos && $severity->weight() < IncidentSeverity::High->weight()) {
            $severity = IncidentSeverity::High;
        }

        $priority = $isSos ? IncidentPriority::Urgent : $severity->defaultPriority();
        $snapshot = $this->snapshot($booking, $app, $data->location);
        $now = CarbonImmutable::now();

        $incident = $this->db->transaction(function () use ($user, $app, $data, $booking, $category, $severity, $priority, $snapshot, $now): SafetyIncident {
            $incident = $this->incidents->createNumbered([
                'booking_id' => $booking->id,
                'reporter_user_id' => $user->id,
                'reporter_type' => $app,
                'trigger' => $data->trigger,
                'category' => $category,
                'severity' => $severity,
                'priority' => $priority,
                'status' => IncidentStatus::New,
                'narrative' => $data->narrative === null || trim($data->narrative) === '' ? null : trim($data->narrative),
                'callback_phone_encrypted' => ($phone = PhoneNumber::normalize($data->callbackPhone) ?? $user->phone_e164) === null ? null : encrypt($phone),
                'location_snapshot' => $snapshot,
                'context' => ['booking_code' => $booking->code, 'booking_status' => $booking->status->value, 'booking_type' => $booking->booking_type->value],
                'occurred_at' => $now,
                'sla_due_at' => $now->addMinutes($priority->slaMinutes()),
            ]);
            $this->thread->recordEvent($incident, IncidentEventType::Created->value, $app->value, $user->id, null, true, ['trigger' => $data->trigger->value, 'category' => $category->value]);

            return $incident;
        });

        $teamNotified = $this->notifier->teamRaised($incident);
        $contactResult = $isSos && $data->notifyTrustedContacts
            ? $this->contactNotifier->sosAlert($user, $incident, $booking, $data->location)
            : null;

        if ($contactResult !== null && $contactResult['total'] > 0) {
            $this->thread->recordEvent($incident, IncidentEventType::TrustedContactsNotified->value, ActorType::System->value, null, null, false, $contactResult);
        }

        return new ReportedIncident($incident, $this->actions($incident, $teamNotified, $contactResult, $isSos, $snapshot), false);
    }

    /**
     * @return list<array{code: string, status: string, message: string}>
     */
    public function actionsOf(SafetyIncident $incident): array
    {
        $actions = $this->contacts->actionsForIncident($incident->id);
        $result = $actions->isEmpty() ? null : [
            'total' => $actions->count(),
            'sent' => $actions->where('status.value', 'sent')->count(),
            'failed' => $actions->where('status.value', 'failed')->count(),
            'skipped' => $actions->where('status.value', 'skipped')->count(),
        ];

        return $this->actions($incident, null, $result, $incident->trigger === IncidentTrigger::Sos, $incident->location_snapshot ?? []);
    }

    private function join(SafetyIncident $existing, User $user, ClientApp $app, ReportIncidentData $data): ReportedIncident
    {
        $this->db->transaction(function () use ($existing, $user, $app, $data): void {
            $locked = $this->incidents->lockById($existing->id);

            if (! $locked instanceof SafetyIncident) {
                return;
            }

            $booking = $locked->booking;
            $snapshot = $booking instanceof Booking ? $this->snapshot($booking, $app, $data->location) : null;

            if ($snapshot !== null && ($snapshot['actor'] ?? null) !== null) {
                $locked->forceFill(['location_snapshot' => $snapshot])->save();
            }

            $this->thread->recordEvent($locked, IncidentEventType::RepeatSos->value, $app->value, $user->id, null, true);
        });

        $this->notifier->feedChanged($existing->refresh());

        return new ReportedIncident($existing, $this->actionsOf($existing), true);
    }

    /**
     * @param array{total: int, sent: int, failed: int, skipped: int}|null $contacts
     * @param array<string, mixed> $snapshot
     * @return list<array{code: string, status: string, message: string}>
     */
    private function actions(SafetyIncident $incident, ?int $teamNotified, ?array $contacts, bool $isSos, array $snapshot): array
    {
        $actions = [['code' => 'incident_recorded', 'status' => 'done', 'message' => (string) __('safety.actions.incident_recorded', ['number' => $incident->number])]];
        $actions[] = ($snapshot['actor'] ?? null) !== null
            ? ['code' => 'location_captured', 'status' => 'done', 'message' => (string) __('safety.actions.location_captured')]
            : ['code' => 'location_captured', 'status' => 'not_available', 'message' => (string) __('safety.actions.location_missing')];
        $actions[] = $teamNotified === 0
            ? ['code' => 'safety_team', 'status' => 'queued', 'message' => (string) __('safety.actions.team_queued')]
            : ['code' => 'safety_team', 'status' => 'done', 'message' => (string) __('safety.actions.team_notified')];

        if ($isSos) {
            $actions[] = match (true) {
                $contacts === null || $contacts['total'] === 0 => ['code' => 'trusted_contacts', 'status' => 'none', 'message' => (string) __('safety.actions.contacts_none')],
                $contacts['sent'] === 0 && $contacts['failed'] === 0 => ['code' => 'trusted_contacts', 'status' => 'skipped', 'message' => (string) __('safety.actions.contacts_skipped')],
                $contacts['failed'] === 0 => ['code' => 'trusted_contacts', 'status' => 'done', 'message' => (string) trans_choice('safety.actions.contacts_sent', $contacts['sent'], ['count' => $contacts['sent']])],
                default => ['code' => 'trusted_contacts', 'status' => 'partial', 'message' => (string) __('safety.actions.contacts_partial', ['sent' => $contacts['sent'], 'failed' => $contacts['failed']])],
            };
        }

        $actions[] = ['code' => 'emergency_services', 'status' => 'not_contacted', 'message' => (string) __('safety.actions.emergency_not_contacted')];

        return $actions;
    }

    private function sosAllowed(Booking $booking): bool
    {
        return $booking->provider_id !== null && ($booking->status->isAssigned() || $booking->status === BookingStatus::CompletedPendingPayment);
    }

    private function resolveBooking(User $user, ClientApp $app, string $uuid): Booking
    {
        if ($app === ClientApp::Customer) {
            return $this->bookings->findOfCustomer($uuid, $user->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        $user->loadMissing('providerProfile');
        $profile = $user->providerProfile ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $this->bookings->findAssignedToProvider($uuid, $profile->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
    }

    /**
     * Where the person is (device fix, or for a provider the last trusted position), where the other side last was and the route
     * points: a snapshot for staff, stored encrypted.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Booking $booking, ClientApp $app, ?LocationFix $fix): array
    {
        $booking->loadMissing('provider');
        $providerLast = $booking->provider === null ? null : $this->locations->findFor($booking->provider);
        $actor = $fix === null ? null : [
            'lat' => round($fix->lat, 6), 'lng' => round($fix->lng, 6), 'accuracy_m' => $fix->accuracyMeters,
            'captured_at' => ($fix->capturedAt ?? CarbonImmutable::now())->toIso8601String(), 'source' => 'device',
        ];
        $provider = $providerLast === null ? null : [
            'lat' => round($providerLast->point->lat, 6), 'lng' => round($providerLast->point->lng, 6), 'accuracy_m' => $providerLast->accuracy_m,
            'captured_at' => $providerLast->recorded_at->toIso8601String(), 'source' => 'last_known',
        ];

        if ($actor === null && $app === ClientApp::Provider) {
            $actor = $provider;
        }

        return [
            'actor' => $actor,
            'counterpart' => $app === ClientApp::Customer ? $provider : null,
            'pickup' => ['lat' => round($booking->pickup_point->lat, 6), 'lng' => round($booking->pickup_point->lng, 6)],
            'dropoff' => $booking->dropoff_point === null ? null : ['lat' => round($booking->dropoff_point->lat, 6), 'lng' => round($booking->dropoff_point->lng, 6)],
        ];
    }
}
