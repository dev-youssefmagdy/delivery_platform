<?php

namespace Tests\Unit\Booking;

use App\Enums\Booking\BookingStatus;
use PHPUnit\Framework\TestCase;

/**
 * The transition graph of Plan §7.2, written out independently of the enum so a change to either one is noticed.
 * Every one of the 15 x 15 pairs is asserted, allowed or not.
 */
class BookingStatusTest extends TestCase
{
    /**
     * @return array<string, list<string>>
     */
    private static function expected(): array
    {
        $cancellations = ['cancelled_by_customer', 'cancelled_by_provider', 'cancelled_by_operations'];

        return [
            'draft' => ['searching_offers', 'cancelled_by_customer', 'cancelled_by_operations'],
            'searching_offers' => ['offers_received', 'provider_selected', 'expired_no_provider', 'cancelled_by_customer', 'cancelled_by_operations'],
            'offers_received' => ['searching_offers', 'provider_selected', 'expired_no_provider', 'cancelled_by_customer', 'cancelled_by_operations'],
            'provider_selected' => ['provider_heading_to_pickup', 'searching_offers', ...$cancellations],
            'provider_heading_to_pickup' => ['provider_arrived', 'searching_offers', ...$cancellations],
            'provider_arrived' => ['in_progress', 'searching_offers', ...$cancellations],
            'in_progress' => ['completed_pending_payment', 'completed', 'delivery_attempt_failed', 'cancelled_by_operations'],
            'delivery_attempt_failed' => ['in_progress', 'cancelled_by_operations', 'disputed'],
            'completed_pending_payment' => ['completed', 'disputed'],
            'completed' => ['disputed'],
            'disputed' => ['completed'],
            'cancelled_by_customer' => [],
            'cancelled_by_provider' => [],
            'cancelled_by_operations' => [],
            'expired_no_provider' => [],
        ];
    }

    public function test_the_expectation_covers_every_status(): void
    {
        $this->assertEqualsCanonicalizing(BookingStatus::values(), array_keys(self::expected()));
    }

    public function test_every_pair_of_statuses_matches_the_plan(): void
    {
        $checked = 0;

        foreach (BookingStatus::cases() as $from) {
            foreach (BookingStatus::cases() as $to) {
                $allowed = in_array($to->value, self::expected()[$from->value], true);
                $this->assertSame($allowed, $from->canTransitionTo($to), "{$from->value} -> {$to->value}");
                $checked++;
            }
        }

        $this->assertSame(225, $checked);
    }

    public function test_allowed_transitions_agree_with_can_transition_to_and_have_no_duplicates(): void
    {
        foreach (BookingStatus::cases() as $from) {
            $targets = array_map(static fn (BookingStatus $status): string => $status->value, $from->allowedTransitions());
            $this->assertSame($targets, array_values(array_unique($targets)), "duplicates from {$from->value}");
            $this->assertEqualsCanonicalizing(self::expected()[$from->value], $targets, $from->value);
        }
    }

    public function test_nothing_leads_back_into_draft_and_no_status_leads_to_itself(): void
    {
        foreach (BookingStatus::cases() as $from) {
            $this->assertFalse($from->canTransitionTo(BookingStatus::Draft), $from->value);
            $this->assertFalse($from->canTransitionTo($from), $from->value);
        }
    }

    public function test_final_statuses_are_the_cancellations_and_the_expired_search(): void
    {
        $final = array_values(array_filter(BookingStatus::cases(), static fn (BookingStatus $status): bool => $status->isFinal()));

        $this->assertEqualsCanonicalizing(
            ['cancelled_by_customer', 'cancelled_by_provider', 'cancelled_by_operations', 'expired_no_provider'],
            array_map(static fn (BookingStatus $status): string => $status->value, $final),
        );
    }

    public function test_in_flight_assigned_and_searching_sets(): void
    {
        $values = static fn (array $statuses): array => array_map(static fn (BookingStatus $status): string => $status->value, $statuses);

        $this->assertEqualsCanonicalizing(
            ['draft', 'searching_offers', 'offers_received', 'provider_selected', 'provider_heading_to_pickup', 'provider_arrived', 'in_progress', 'delivery_attempt_failed', 'completed_pending_payment'],
            $values(BookingStatus::inFlight()),
        );
        $this->assertEqualsCanonicalizing(['provider_selected', 'provider_heading_to_pickup', 'provider_arrived', 'in_progress', 'delivery_attempt_failed'], $values(BookingStatus::assigned()));
        $this->assertEqualsCanonicalizing(['searching_offers', 'offers_received'], $values(BookingStatus::searching()));
        $this->assertFalse(BookingStatus::Completed->isInFlight());
        $this->assertFalse(BookingStatus::Disputed->isInFlight());
        $this->assertTrue(BookingStatus::CompletedPendingPayment->isInFlight());
    }
}
