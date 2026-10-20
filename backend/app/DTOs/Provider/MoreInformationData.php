<?php

namespace App\DTOs\Provider;

/**
 * "Request more information": an overall message for the provider and optional per-document reasons.
 */
final readonly class MoreInformationData
{
    /**
     * @param array<string, string> $documentIssues document uuid => reason (provider and vehicle documents)
     */
    public function __construct(
        public string $reason,
        public array $documentIssues = [],
    ) {}
}
