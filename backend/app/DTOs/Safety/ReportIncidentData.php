<?php

namespace App\DTOs\Safety;

use App\Enums\Safety\IncidentCategory;
use App\Enums\Safety\IncidentTrigger;

/**
 * What the app sends to open a safety case: the job, how it was raised, the category, the person's words, the GPS fix
 * and the number to call back (defaults to the account phone).
 */
final readonly class ReportIncidentData
{
    public function __construct(
        public string $bookingUuid,
        public IncidentTrigger $trigger,
        public IncidentCategory $category,
        public ?string $narrative,
        public ?LocationFix $location,
        public ?string $callbackPhone,
        public bool $notifyTrustedContacts = true,
    ) {}
}
