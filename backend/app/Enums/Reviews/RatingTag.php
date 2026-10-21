<?php

namespace App\Enums\Reviews;

use App\Enums\Common\ActorType;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Structured feedback chips of a rating (SRS §6.7). Customers tag providers, providers tag customers.
 */
enum RatingTag: string implements HasLabel
{
    use EnumHelpers;

    // About a provider.
    case Professional = 'professional';
    case CleanVehicle = 'clean_vehicle';
    case SafeDriving = 'safe_driving';
    case GoodCommunication = 'good_communication';
    case OnTime = 'on_time';
    case CarefulWithItems = 'careful_with_items';
    case Late = 'late';
    case UnsafeDriving = 'unsafe_driving';
    case Rude = 'rude';
    case DirtyVehicle = 'dirty_vehicle';

    // About a customer.
    case Respectful = 'respectful';
    case ClearInstructions = 'clear_instructions';
    case ReadyAtPickup = 'ready_at_pickup';
    case Disrespectful = 'disrespectful';
    case NotReadyAtPickup = 'not_ready_at_pickup';

    /**
     * @return list<self>
     */
    public static function forRatee(ActorType $ratee): array
    {
        return match ($ratee) {
            ActorType::Provider => [self::Professional, self::CleanVehicle, self::SafeDriving, self::GoodCommunication, self::OnTime, self::CarefulWithItems, self::Late, self::UnsafeDriving, self::Rude, self::DirtyVehicle],
            ActorType::Customer => [self::Respectful, self::ClearInstructions, self::ReadyAtPickup, self::Disrespectful, self::NotReadyAtPickup, self::Late],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function valuesForRatee(ActorType $ratee): array
    {
        return array_map(static fn (self $tag): string => $tag->value, self::forRatee($ratee));
    }

    public function label(): string
    {
        return __("enums.rating_tag.{$this->value}");
    }
}
