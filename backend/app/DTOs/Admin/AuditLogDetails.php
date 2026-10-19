<?php

namespace App\DTOs\Admin;

use App\Models\AdminAuditLog;

/**
 * An audit entry with its comparison rows and the resolved actor name.
 */
final readonly class AuditLogDetails
{
    /**
     * @param list<AuditDiffRow> $rows
     */
    public function __construct(
        public AdminAuditLog $log,
        public array $rows,
        public ?string $actorName,
    ) {}
}
