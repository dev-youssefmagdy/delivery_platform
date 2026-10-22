<?php

namespace App\DTOs\Support;

use App\Enums\Support\TicketVisibility;
use Illuminate\Http\UploadedFile;

/**
 * An agent's answer or internal note. `macroId` records which canned reply the text started from.
 */
final readonly class StaffReplyData
{
    /**
     * @param list<UploadedFile> $attachments
     */
    public function __construct(
        public string $body,
        public TicketVisibility $visibility,
        public ?int $macroId = null,
        public array $attachments = [],
    ) {}
}
