<?php

namespace App\DTOs\Payments;

use App\Enums\Payments\WebhookOutcome;
use App\Models\Payment;

final readonly class WebhookProcessingResult
{
    public function __construct(public WebhookOutcome $outcome, public ?Payment $payment = null, public ?string $note = null) {}

    /**
     * Final decisions are never applied again; unmatched, mismatching and failed events wait for a retry or an operator.
     */
    public function isFinal(): bool
    {
        return $this->outcome === WebhookOutcome::Processed || $this->outcome === WebhookOutcome::Ignored;
    }
}
