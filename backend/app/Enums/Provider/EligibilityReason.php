<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a provider cannot go online / receive requests (Plan I11). Returned as typed reasons by
 * `ProviderEligibilityService::check()`; the apps map each code to text. P2.2 and P3.x add the location, active-job
 * and debt-limit reasons to the same service.
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

    public function label(): string
    {
        return __("enums.eligibility_reason.{$this->value}");
    }
}
