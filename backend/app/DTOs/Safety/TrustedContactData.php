<?php

namespace App\DTOs\Safety;

use App\Enums\Safety\TrustedContactRelationship;

final readonly class TrustedContactData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?TrustedContactRelationship $relationship,
        public bool $consentConfirmed,
        public bool $notifyOnSos,
        public bool $shareTrip,
        public bool $shareLocationOnSos,
    ) {}
}
