<?php

namespace App\DTOs\Booking;

/**
 * Validated input of a rating: 1 to 5 stars, structured tags and an optional private comment.
 */
final readonly class RatingData
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public int $stars,
        public array $tags = [],
        public ?string $comment = null,
    ) {}
}
