<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * A pending task shown on the driver's verification screen. The app maps each code to a screen and text.
 */
enum OnboardingTask: string implements HasLabel
{
    use EnumHelpers;

    case CompleteProfile = 'complete_profile';
    case SelectServices = 'select_services';
    case AddVehicle = 'add_vehicle';
    case AddVehiclePhotos = 'add_vehicle_photos';
    case SubmitVehicle = 'submit_vehicle';
    case UploadDocument = 'upload_document';
    case ReplaceRejectedDocument = 'replace_rejected_document';
    case RenewExpiredDocument = 'renew_expired_document';
    case FixVehicle = 'fix_vehicle';
    case AcceptPolicies = 'accept_policies';
    case SubmitApplication = 'submit_application';

    public function label(): string
    {
        return __("enums.onboarding_task.{$this->value}");
    }
}
