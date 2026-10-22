<?php

namespace App\DTOs\Support;

use App\Enums\Common\ActorType;
use App\Models\BookingMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What the chat endpoint returns: the messages and the facts the screen needs (who the reader is, whether sending is still
 * allowed and until when, how many of the other side's messages are unread).
 */
final readonly class ChatPage
{
    /**
     * @param Collection<int, BookingMessage> $messages
     */
    public function __construct(
        public Collection $messages,
        public ActorType $role,
        public string $bookingUuid,
        public bool $canSend,
        public ?CarbonImmutable $closesAt,
        public int $unread,
        public bool $hasMore,
    ) {}
}
