<?php

namespace App\DTOs\Support;

use App\Enums\Support\TicketCategory;
use Illuminate\Http\UploadedFile;

/**
 * Input of "open a ticket" from an app. Links are public ids of things the person owns; the service checks ownership.
 */
final readonly class OpenTicketData
{
    /**
     * @param list<UploadedFile> $attachments
     */
    public function __construct(
        public TicketCategory $category,
        public string $subject,
        public string $body,
        public ?string $bookingUuid = null,
        public ?string $paymentUuid = null,
        public ?string $payoutUuid = null,
        public array $attachments = [],
    ) {}
}
