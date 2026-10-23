<?php

namespace App\DTOs\Support;

use App\Enums\Support\TicketCategory;

/**
 * The public contact form (already validated). The visitor has no account: the contact details live in the ticket context.
 */
final readonly class WebsiteContactData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public TicketCategory $category,
        public string $subject,
        public string $message,
        public string $locale,
        public ?string $ip,
    ) {}
}
