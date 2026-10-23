<?php

namespace App\DTOs\Content;

/**
 * Validated input for creating or updating a workflow content entity (source fields only: the derived sanitized
 * HTML and the workflow columns are set by the services).
 */
interface ContentData
{
    /**
     * @return array<string, mixed>
     */
    public function attributes(): array;

    public function lockVersion(): ?int;
}
