<?php

namespace App\DTOs\Support;

use Illuminate\Http\UploadedFile;

final readonly class TicketReplyData
{
    /**
     * @param list<UploadedFile> $attachments
     */
    public function __construct(public string $body, public array $attachments = []) {}
}
