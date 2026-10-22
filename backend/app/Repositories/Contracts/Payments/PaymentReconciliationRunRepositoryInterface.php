<?php

namespace App\Repositories\Contracts\Payments;

use App\Models\PaymentReconciliationRun;
use Illuminate\Database\Eloquent\Builder;

interface PaymentReconciliationRunRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): PaymentReconciliationRun;

    public function latest(): ?PaymentReconciliationRun;

    /**
     * @return Builder<PaymentReconciliationRun>
     */
    public function datatableQuery(?string $status = null): Builder;
}
