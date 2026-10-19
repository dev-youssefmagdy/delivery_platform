<?php

namespace App\DTOs\Admin;

/**
 * One attribute of an audit before/after comparison (values are display strings, secrets masked).
 */
final readonly class AuditDiffRow
{
    public function __construct(
        public string $key,
        public ?string $before,
        public ?string $after,
        public bool $changed,
    ) {}
}
