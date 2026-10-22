<?php

namespace App\DTOs\Support;

use Illuminate\Http\UploadedFile;

final readonly class ChatMessageData
{
    public function __construct(public ?string $body, public ?UploadedFile $attachment = null) {}
}
