<?php

namespace App\DTOs\Lead;

/**
 * What the public provider form sends (already validated). Contact data and interest only.
 */
final readonly class LeadSubmissionData
{
    public function __construct(
        public string $name,
        public string $phoneE164,
        public ?string $email,
        public ?int $cityId,
        public ?string $cityOther,
        public ?int $serviceCategoryId,
        public ?string $vehicleNote,
        public ?string $message,
        public string $locale,
        public ?string $ip,
    ) {}
}
