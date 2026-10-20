<?php

namespace App\DTOs\Customers;

use App\Http\Requests\Admin\Customers\SendHelpMessageRequest;

/**
 * Validated input of "send help message": the in-app message and the audit reason for sending it.
 */
final readonly class HelpMessageData
{
    public function __construct(
        public string $subject,
        public string $body,
        public string $reason,
    ) {}

    public static function fromRequest(SendHelpMessageRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(trim((string) $data['subject']), trim((string) $data['body']), (string) $request->reason());
    }
}
