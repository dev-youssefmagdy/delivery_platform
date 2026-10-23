<?php

namespace App\Services\Booking;

use App\Enums\Common\ActorType;
use App\Enums\Settings\SettingKey;
use App\Models\Booking;
use App\Models\BookingRating;
use App\Models\BookingStatusEvent;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Services\Scope\BookingLoadPresenter;
use App\Services\Settings\SettingsService;
use Carbon\CarbonInterface;

/**
 * View data of the lazily loaded tabs on the booking page: status timeline, snapshots, PIN status and proofs, ratings.
 * Nothing sensitive leaves here: the PIN is never read, snapshots lose the route polyline (and the arrival GPS evidence
 * unless the admin may see exact locations), phone numbers are not part of any tab.
 */
final class BookingDetailTabsService
{
    public function __construct(
        private readonly BookingStatusEventRepositoryInterface $events,
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly SafetyIncidentRepositoryInterface $incidents,
        private readonly RatingRepositoryInterface $ratings,
        private readonly SettingsService $settings,
        private readonly BookingLoadPresenter $loads,
    ) {}

    /**
     * @return list<array{title: string, time: string, description: string|null, badge: \App\Enums\Booking\BookingStatus|null, actor: string, reason: string|null}>
     */
    public function timeline(Booking $booking): array
    {
        return $this->events->forBooking($booking)->map(function (BookingStatusEvent $event): array {
            $annotation = $event->from_status === $event->to_status;
            $key = (string) $event->reason;
            $known = $annotation && trans()->has("admin.bookings.timeline.events.{$key}");

            return [
                'title' => $annotation ? ($known ? (string) __("admin.bookings.timeline.events.{$key}") : (string) __('admin.bookings.timeline.note')) : $event->to_status->label(),
                'time' => $this->time($event->created_at),
                'description' => $annotation || $event->from_status === null ? null : (string) __('admin.bookings.timeline.from', ['status' => $event->from_status->label()]),
                'badge' => $annotation ? null : $event->to_status,
                'actor' => ActorType::tryFrom($event->actor_type)?->label() ?? $event->actor_type,
                'reason' => $annotation && $known ? null : $event->reason,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshots(Booking $booking, bool $canViewPii): array
    {
        $route = $booking->route_snapshot;

        if (is_array($route)) {
            unset($route['polyline']);
        }

        return array_filter([
            'service' => $booking->service_snapshot,
            'policy' => $booking->policy_snapshot,
            'reference_estimate' => $booking->reference_estimate,
            'route' => $route,
            'commission' => $booking->commission_snapshot,
            // Moving / freight (P4.2): what the customer asked for, the accepted scope and the load lines (free text only with view_pii).
            'requested_scope' => $booking->requested_scope,
            'agreed_scope' => $booking->agreed_scope,
            'load' => $this->load($booking, $canViewPii),
            'cancellation_decision' => $booking->cancellation_decision,
            'arrival_evidence' => $canViewPii ? $booking->arrival_evidence : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function load(Booking $booking, bool $canViewPii): ?array
    {
        $load = $this->loads->load($booking);

        if ($load === null || $canViewPii) {
            return $load;
        }

        foreach ($load['items'] as &$item) {
            $item['label'] = $item['kind'] === 'free' ? null : $item['label'];
            $item['note'] = null;
        }

        return $load;
    }

    /**
     * PIN status (never the PIN), support flag, safety cases and the reports filed on the booking. Proofs and the delivery summary live in the "Delivery and load" tab (P4.4) only.
     *
     * @return array{pin: array<string, mixed>, incidents: list<array<string, mixed>>, reports: list<array<string, string>>, flag: array{at: string, reason: string|null}|null}
     */
    public function safety(Booking $booking, bool $canViewPii = false): array
    {
        $required = (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false);
        $max = $this->settings->int(SettingKey::RidePinMaxAttempts);

        return [
            'pin' => [
                'required' => $required,
                'verified_at' => $booking->pin_verified_at === null ? null : $this->time($booking->pin_verified_at),
                'locked_at' => $booking->pin_locked_at === null ? null : $this->time($booking->pin_locked_at),
                'attempts' => $booking->pin_attempts,
                'max_attempts' => $max,
            ],
            // Safety cases raised from the apps (P5.1): structure only; the case itself is in the Safety Center.
            'incidents' => $this->incidents->forBooking($booking->id)->map(fn ($incident): array => [
                'uuid' => $incident->uuid,
                'number' => $incident->number,
                'category' => $incident->category->label(),
                'trigger' => $incident->trigger->label(),
                'severity' => $incident->severity,
                'status' => $incident->status,
                'reporter' => $incident->reporter_type->label(),
                'time' => $this->time($incident->created_at),
                'model' => $incident,
            ])->values()->all(),
            'reports' => $this->tickets->forBooking($booking->id)->map(fn ($ticket): array => [
                'category' => $ticket->number.' · '.$ticket->category->label(),
                'status' => $ticket->status->label(),
                'reporter' => ActorType::from($ticket->app->value)->label(),
                'time' => $this->time($ticket->created_at),
            ])->values()->all(),
            'flag' => $booking->support_flagged_at === null ? null : [
                'at' => $this->time($booking->support_flagged_at),
                'reason' => $booking->support_flag_reason,
            ],
        ];
    }

    /**
     * @return list<array{from: string, stars: int, tags: list<string>, comment: string|null, status: string, time: string}>
     */
    public function ratings(Booking $booking): array
    {
        return $this->ratings->forBooking($booking->id)->map(fn (BookingRating $rating): array => [
            'from' => ActorType::tryFrom($rating->rater_type)?->label() ?? $rating->rater_type,
            'stars' => $rating->stars,
            'tags' => (array) ($rating->tags ?? []),
            'comment' => $rating->comment,
            'status' => $rating->moderation_status->label(),
            'time' => $this->time($rating->created_at),
        ])->values()->all();
    }

    private function time(CarbonInterface $at): string
    {
        return $at->copy()->setTimezone((string) config('admin.display_timezone', 'UTC'))->locale(app()->getLocale())->translatedFormat('Y-m-d H:i');
    }
}
