<?php

namespace App\Repositories\Contracts\Payout;

use App\DTOs\Payout\PayoutBatchFilterData;
use App\Models\PayoutBatch;
use Illuminate\Database\Eloquent\Builder;

interface PayoutBatchRepositoryInterface
{
    public function find(int $id): ?PayoutBatch;

    public function lockById(int $id): ?PayoutBatch;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): PayoutBatch;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(PayoutBatch $batch, array $attributes): PayoutBatch;

    /**
     * @return Builder<PayoutBatch>
     */
    public function datatableQuery(PayoutBatchFilterData $filters): Builder;
}
