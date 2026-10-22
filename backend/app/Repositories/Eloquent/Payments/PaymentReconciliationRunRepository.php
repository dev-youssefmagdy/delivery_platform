<?php

namespace App\Repositories\Eloquent\Payments;

use App\Models\PaymentReconciliationRun;
use App\Repositories\Contracts\Payments\PaymentReconciliationRunRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class PaymentReconciliationRunRepository implements PaymentReconciliationRunRepositoryInterface
{
    public function create(array $attributes): PaymentReconciliationRun
    {
        return PaymentReconciliationRun::query()->create($attributes);
    }

    public function latest(): ?PaymentReconciliationRun
    {
        return PaymentReconciliationRun::query()->orderByDesc('ran_at')->orderByDesc('id')->first();
    }

    public function datatableQuery(?string $status = null): Builder
    {
        return PaymentReconciliationRun::query()->when($status !== null, static fn (Builder $query) => $query->where('status', $status));
    }
}
