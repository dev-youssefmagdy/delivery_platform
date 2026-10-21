<?php

namespace App\DTOs\Booking;

/**
 * What a provider sends when it answers a request. No totals, no commission, no status: the server computes
 * everything else. A null amount means "I accept the customer's fare".
 */
final readonly class SubmitOfferData
{
    /**
     * @param array<string, mixed>|null $scope structured scope summary (vehicle class, helpers, stairs, waiting ...)
     */
    public function __construct(
        public ?int $amountMinor = null,
        public ?string $currency = null,
        public ?string $note = null,
        public ?array $scope = null,
        public ?int $etaMinutes = null,
        public ?string $idempotencyKey = null,
    ) {}
}
