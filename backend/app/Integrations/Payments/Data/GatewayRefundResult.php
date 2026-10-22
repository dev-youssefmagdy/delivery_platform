<?php

namespace App\Integrations\Payments\Data;

/**
 * The processor's immediate answer to a refund call. `processing` means the final result arrives by webhook.
 */
final readonly class GatewayRefundResult
{
    public const SUCCEEDED = 'succeeded';

    public const PROCESSING = 'processing';

    public const FAILED = 'failed';

    public function __construct(public string $reference, public string $state, public ?string $failureCode = null) {}
}
