<?php

namespace App\Enums\Booking;

use App\Enums\Common\ActorType;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Cancellation reason categories (SRS §8.4). Customers and providers pick from their own list (a cancellation policy
 * narrows it further); `other` needs a note. Operations write free-text reasons.
 */
enum CancellationReason: string implements HasLabel
{
    use EnumHelpers;

    // Customer side.
    case ChangedMind = 'changed_mind';
    case WrongPickup = 'wrong_pickup';
    case FoundAnotherRide = 'found_another_ride';
    case DriverTooFar = 'driver_too_far';
    case DriverAskedToCancel = 'driver_asked_to_cancel';
    case WaitedTooLong = 'waited_too_long';

    // Provider side.
    case CustomerNoShow = 'customer_no_show';
    case CustomerUnreachable = 'customer_unreachable';
    case VehicleProblem = 'vehicle_problem';
    case Emergency = 'emergency';
    case WrongRequestDetails = 'wrong_request_details';
    case UnsafePickup = 'unsafe_pickup';

    case Other = 'other';

    /**
     * @return list<self>
     */
    public static function forActor(ActorType $actor): array
    {
        return match ($actor) {
            ActorType::Customer => [self::ChangedMind, self::WrongPickup, self::FoundAnotherRide, self::DriverTooFar, self::DriverAskedToCancel, self::WaitedTooLong, self::Other],
            ActorType::Provider => [self::CustomerNoShow, self::CustomerUnreachable, self::VehicleProblem, self::Emergency, self::WrongRequestDetails, self::UnsafePickup, self::Other],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function valuesForActor(ActorType $actor): array
    {
        return array_map(static fn (self $reason): string => $reason->value, self::forActor($actor));
    }

    public function label(): string
    {
        return __("enums.cancellation_reason.{$this->value}");
    }
}
