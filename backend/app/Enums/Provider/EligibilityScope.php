<?php

namespace App\Enums\Provider;

/**
 * What an eligibility check is for. `Onboarding` covers approval, documents, vehicles and services (the verification
 * screens); `Working` adds the runtime conditions of going online and receiving requests: fresh location, no
 * conflicting active job and the cash debt limit.
 */
enum EligibilityScope: string
{
    case Onboarding = 'onboarding';
    case Working = 'working';
}
