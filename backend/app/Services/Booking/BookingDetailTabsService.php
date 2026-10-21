<?php

namespace App\Services\Booking;

use App\Enums\Common\ActorType;
use App\Enums\Settings\SettingKey;
use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\BookingRating;
use App\Models\BookingStatusEvent;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingReportRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
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
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly BookingReportRepositoryInterface $reports,
        private readonly RatingRepositoryInterface $ratings,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return list<array{title: string, time: string, description: string|null, badge: \App\Enums\Booking\BookingStatus|null, actor: string, reason: string|null}>
     */
    public function timeline(Booking $booking): array
    {
        return $this->events->forBooking($booking)->map(function (BookingStatusEvent $event): array {
            $annotation = $event->from_status === $event->to_status && $event->from_status !== null;
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
            'agreed_scope' => $booking->agreed_scope,
            'cancellation_decision' => $booking->cancellation_decision,
            'arrival_evidence' => $canViewPii ? $booking->arrival_evidence : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * PIN status (never the PIN), support flag, proofs and the reports filed on the booking.
     *
     * @return array{pin: array<string, mixed>, proofs: list<array<string, string>>, reports: list<array<string, string>>, flag: array{at: string, reason: string|null}|null}
     */
    public function safety(Booking $booking): array
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
            'proofs' => $this->proofs->forBooking($booking->id)->map(fn (BookingProof $proof): array => [
                'type' => $proof->proof_type->label(),
                'actor' => ActorType::tryFrom($proof->actor_type)?->label() ?? $proof->actor_type,
                'time' => $this->time($proof->created_at),
            ])->values()->all(),
            'reports' => $this->reports->forBooking($booking->id)->map(fn ($report): array => [
                'category' => $report->category->label(),
                'status' => $report->status->label(),
                'reporter' => ActorType::tryFrom($report->reporter_type)?->label() ?? $report->reporter_type,
                'time' => $this->time($report->created_at),
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
            'tags' => array_values((array) ($rating->tags ?? [])),
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
