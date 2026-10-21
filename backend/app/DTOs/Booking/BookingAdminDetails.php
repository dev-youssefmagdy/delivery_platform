<?php

namespace App\DTOs\Booking;

use App\Models\Booking;

/**
 * Everything the booking show page renders outside its lazy tabs: the booking with its relations, the flags that decide
 * which operator actions are offered, and whether the viewer may see contact data and exact addresses.
 */
final readonly class BookingAdminDetails
{
    /**
     * @param list<string> $availableOverrides values of BookingOverrideAction the booking allows right now
     * @param list<string> $forceTargets values of the statuses a forced transition may target
     */
    public function __construct(
        public Booking $booking,
        public bool $canViewPii,
        public array $availableOverrides,
        public array $forceTargets,
        public ?string $customerPhoneMasked,
        public ?string $providerPhoneMasked,
        public bool $pinRequired,
        public int $offerCount,
    ) {}
}
