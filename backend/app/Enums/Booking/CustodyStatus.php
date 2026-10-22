<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Where a parcel is in the chain of custody (`bookings.custody_status`). Every move is also appended to the booking
 * timeline as a status-less event `custody_<value>`. The order is enforced by `allowedNext()`.
 */
enum CustodyStatus: string implements HasLabel
{
    use EnumHelpers;

    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case ArrivedDropoff = 'arrived_dropoff';
    case AttemptFailed = 'attempt_failed';
    case Returning = 'returning';
    case Delivered = 'delivered';
    case Returned = 'returned';

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::PickedUp => [self::InTransit, self::ArrivedDropoff, self::Delivered],
            self::InTransit => [self::ArrivedDropoff, self::Delivered],
            self::ArrivedDropoff => [self::AttemptFailed, self::Delivered],
            self::AttemptFailed => [self::InTransit, self::Returning],
            self::Returning => [self::Returned],
            self::Delivered, self::Returned => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function timelineReason(): string
    {
        return 'custody_'.$this->value;
    }

    public function label(): string
    {
        return __("enums.custody_status.{$this->value}");
    }
}
