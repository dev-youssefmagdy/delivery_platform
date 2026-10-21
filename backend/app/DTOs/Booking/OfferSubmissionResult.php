<?php

namespace App\DTOs\Booking;

use App\Models\BookingOffer;

/**
 * The saved offer plus what only the submitting provider is told: fairness hints (informational, never a price
 * change) and the earnings preview. `replayed` is true when the same request had already been applied.
 */
final readonly class OfferSubmissionResult
{
    /**
     * @param list<string> $fareHints
     * @param array<string, mixed> $earningsPreview
     */
    public function __construct(
        public BookingOffer $offer,
        public bool $replayed,
        public bool $revision,
        public array $fareHints,
        public array $earningsPreview,
    ) {}
}
