<?php

namespace App\DTOs\Customers;

use App\Http\Requests\Admin\Customers\SuspendCustomerRequest;
use Carbon\CarbonImmutable;

/**
 * Validated input of "suspend customer": a mandatory reason and an optional end of the suspension (UTC).
 */
final readonly class SuspendCustomerData
{
    public function __construct(
        public string $reason,
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    public static function fromRequest(SuspendCustomerRequest $request): self
    {
        return new self((string) $request->reason(), $request->expiresAt());
    }
}
