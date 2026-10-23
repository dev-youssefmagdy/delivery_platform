<?php

namespace App\DTOs\Lead;

use Illuminate\Http\Request;

/**
 * Staff edit of a lead: internal notes and owner. `lockVersion` guards against two people saving over each other.
 */
final readonly class LeadUpdateData
{
    public function __construct(
        public ?string $notes,
        public ?int $assignedAdminId,
        public ?int $lockVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $notes = $request->input('notes');
        $assigned = $request->input('assigned_admin_id');
        $lock = $request->input('lock_version');

        return new self(
            is_string($notes) && trim($notes) !== '' ? trim($notes) : null,
            is_numeric($assigned) ? (int) $assigned : null,
            is_numeric($lock) ? (int) $lock : null,
        );
    }
}
