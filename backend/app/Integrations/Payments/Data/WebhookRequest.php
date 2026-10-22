<?php

namespace App\Integrations\Payments\Data;

/**
 * The raw callback as received: the body byte for byte (signatures cover it), lower-cased headers and the query string.
 */
final readonly class WebhookRequest
{
    /**
     * @param array<string, string> $headers lower-cased names
     * @param array<string, string> $query
     */
    public function __construct(public string $body, public array $headers = [], public array $query = []) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
