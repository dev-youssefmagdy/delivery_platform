<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\WebhookOutcome;

/**
 * What the webhook endpoint answers: the outcome and the HTTP status that tells the gateway whether to retry.
 */
final readonly class WebhookReceipt
{
    public function __construct(public WebhookOutcome $outcome, public ?string $eventUuid = null) {}

    /**
     * 2xx for everything the platform has handled for good (including "already seen" and "not relevant"), 202 for events it
     * could not match yet, 4xx for requests that are not authentic, 5xx when processing failed so the gateway retries.
     */
    public function httpStatus(): int
    {
        return match ($this->outcome) {
            WebhookOutcome::Processed, WebhookOutcome::Duplicate, WebhookOutcome::Ignored, WebhookOutcome::Received => 200,
            WebhookOutcome::Unmatched, WebhookOutcome::AmountMismatch => 202,
            WebhookOutcome::InvalidSignature => 401,
            WebhookOutcome::ReplayWindow => 400,
            WebhookOutcome::Failed => 500,
        };
    }
}
