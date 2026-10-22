<?php

namespace App\Jobs\Payments;

use App\Enums\Common\QueueName;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\WebhookOutcome;
use App\Integrations\Payments\Drivers\SandboxPaymentGateway;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sandbox only: delivers the signed webhook a real gateway would send some seconds after a payment was started. It goes
 * through the same PaymentWebhookService::receive() as the HTTP callback, so verification, deduplication and processing
 * are exactly the production ones. When the payment row is not committed yet (`unmatched`) the delivery is retried.
 */
class DeliverSandboxWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(public string $type, public array $data, public ?string $eventId = null)
    {
        $this->onQueue(QueueName::Critical->value);
        $this->eventId ??= 'evt_'.bin2hex(random_bytes(12));
    }

    public function handle(SandboxPaymentGateway $sandbox, PaymentWebhookService $webhooks): void
    {
        // The bank side changes once, at the first delivery attempt (a retry only re-sends the same event).
        if ($this->attempts() <= 1) {
            $sandbox->applyBankSide($this->type, $this->data);
        }

        $result = $webhooks->receive(PaymentGatewayName::Sandbox, $sandbox->signedRequest($this->type, $this->data, $this->eventId));

        if ($result->outcome === WebhookOutcome::Unmatched && $this->attempts() < $this->tries) {
            $this->release(2);
        }
    }
}
