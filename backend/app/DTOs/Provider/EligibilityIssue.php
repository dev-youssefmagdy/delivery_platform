<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\EligibilityReason;

/**
 * One reason a provider is not eligible, with what it refers to (a document type code, a vehicle uuid, ...).
 */
final readonly class EligibilityIssue
{
    public function __construct(
        public EligibilityReason $reason,
        public ?string $reference = null,
    ) {}
}
