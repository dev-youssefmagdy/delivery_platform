<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a safety report is about (SRS §13.1). The category sets the default severity; staff can change it.
 */
enum IncidentCategory: string implements HasLabel
{
    use EnumHelpers;

    case Emergency = 'emergency';
    case Accident = 'accident';
    case Medical = 'medical';
    case Threat = 'threat';
    case Harassment = 'harassment';
    case UnsafeDriving = 'unsafe_driving';
    case Theft = 'theft';
    case IdentityMismatch = 'identity_mismatch';
    case Other = 'other';

    public function label(): string
    {
        return __("safety.enums.category.{$this->value}");
    }

    public function defaultSeverity(): IncidentSeverity
    {
        return match ($this) {
            self::Emergency, self::Medical, self::Threat => IncidentSeverity::Critical,
            self::Accident, self::Harassment, self::Theft => IncidentSeverity::High,
            self::UnsafeDriving, self::IdentityMismatch => IncidentSeverity::Medium,
            self::Other => IncidentSeverity::Low,
        };
    }
}
