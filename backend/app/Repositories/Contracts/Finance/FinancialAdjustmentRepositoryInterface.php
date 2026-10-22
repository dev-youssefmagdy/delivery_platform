<?php

namespace App\Repositories\Contracts\Finance;

use App\DTOs\Finance\AdjustmentFilterData;
use App\Models\FinancialAdjustmentRequest;
use Illuminate\Database\Eloquent\Builder;

interface FinancialAdjustmentRepositoryInterface
{
    public function find(int $id): ?FinancialAdjustmentRequest;

    public function lockById(int $id): ?FinancialAdjustmentRequest;

    public function findByIdempotencyKey(string $key): ?FinancialAdjustmentRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): FinancialAdjustmentRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(FinancialAdjustmentRequest $request, array $attributes): FinancialAdjustmentRequest;

    public function pendingCount(): int;

    /**
     * @return Builder<FinancialAdjustmentRequest>
     */
    public function datatableQuery(AdjustmentFilterData $filters): Builder;
}
