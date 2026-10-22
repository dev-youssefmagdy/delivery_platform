<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a provider cannot go online / receive requests (Plan I11). Returned as typed reasons by
 * `ProviderEligibilityService::check()`; the apps map each code to text. The runtime reasons (stale location, active job, cash debt
 * limit) are only evaluated for `EligibilityScope::Working`.
 */
enum EligibilityReason: string implements HasLabel
{
    use EnumHelpers;

    case NotApproved = 'not_approved';
    case ProviderSuspended = 'provider_suspended';
    case AccountBlocked = 'account_blocked';
    case DocumentMissing = 'document_missing';
    case DocumentNotApproved = 'document_not_approved';
    case DocumentExpired = 'document_expired';
    case NoApprovedVehicle = 'no_approved_vehicle';
    case VehicleNotApproved = 'vehicle_not_approved';
    case ServiceNotApproved = 'service_not_approved';
    case VehicleCategoryMismatch = 'vehicle_category_mismatch';
    case LocationStale = 'location_stale';
    case ActiveJobExists = 'active_job_exists';
    case CashDebtLimitExceeded = 'cash_debt_limit_exceeded';
    case CancellationCooldown = 'cancellation_cooldown';

    public function label(): string
    {
        return __("enums.eligibility_reason.{$this->value}");
    }
}
