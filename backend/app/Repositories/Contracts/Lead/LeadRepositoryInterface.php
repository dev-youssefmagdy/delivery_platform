<?php

namespace App\Repositories\Contracts\Lead;

use App\DTOs\Lead\LeadFilterData;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;

interface LeadRepositoryInterface
{
    public function findByDuplicateKey(string $key): ?Lead;

    /**
     * Inserts the lead and sets its human reference (LD-000123).
     *
     * @param array<string, mixed> $attributes
     */
    public function createNumbered(array $attributes): Lead;

    public function lockById(int $id): ?Lead;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Lead $lead, array $attributes): Lead;

    public function countByStatus(string $status): int;

    /**
     * @return Builder<Lead>
     */
    public function datatableQuery(LeadFilterData $filters): Builder;
}
